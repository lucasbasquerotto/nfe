<?php
require 'vendor/autoload.php';

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;

$baseDir = '/var/main';
$baseConfigDir = $baseDir . '/config';
$baseStateDir = $baseDir . '/state';
$baseOutputDir = $baseDir . '/output';
$baseDataDir = $baseOutputDir . '/dfe';

if (!file_exists($baseConfigDir)) {
	mkdir($baseConfigDir, 0700, true);
}

if (!file_exists($baseStateDir)) {
	mkdir($baseStateDir, 0744, true);
}

if (!file_exists($baseOutputDir)) {
	mkdir($baseOutputDir, 0744, true);
}

if (!file_exists($baseDataDir)) {
	mkdir($baseDataDir, 0744, true);
}

$envJson = file_get_contents($baseConfigDir . '/env.json');
$env = json_decode($envJson, true);

$clientsJson = file_get_contents($baseConfigDir . '/clients.json');
$clients = json_decode($clientsJson, true);

$clientsVarName = getEnv("clients");

$clientIds = $env[$clientsVarName];

foreach ($clientIds as $clientId) {
	$item = $clients[$clientId];

	$currentStateDir = $baseStateDir . '/' . $clientId;	

	if (!file_exists($currentStateDir)) {
		mkdir($currentStateDir, 0744, true);
	}

	$state = null;

	if (file_exists($currentStateDir . '/state.json')){
		$stateJson = file_get_contents($currentStateDir . '/state.json');
		$state = json_decode($stateJson);
	}

	$state = (!isset($state) || is_null($state)) ? (new stdClass()) : $state;
	
	$currentDir = $baseDataDir . '/' . $clientId;	

	if (!file_exists($currentDir)) {
		mkdir($currentDir, 0744, true);
	}
	
	$certificadoDigital = file_get_contents($baseConfigDir . '/' . $clientId . '/certificado.pfx');

	$config = [
		"atualizacao" => date('Y-m-d h:i:s'),
		"tpAmb" => $env['tpAmb'],
		"razaosocial" => $item['razaosocial'],
		"cnpj" => $item['cnpj'],
		"ie" => $item['ie'],
		"siglaUF" => $item['siglaUF'],
		"schemes" => "PL_009_V4",
		"versao" => '4.00',
		"tokenIBPT" => "AAAAAAA",
		"CSC" => $item['CSC'],
		"CSCid" => $item['CSCid']
	];

	$configJson = json_encode($config);
	
	$loopLimit = $env['loopLimit'];
	$loopLimit = (!isset($loopLimit) || is_null($loopLimit)) ? 0 : $loopLimit;

	//este número deverá vir de uma camada de persistência nas próximas buscas para reduzir 
	//a quantidade de documentos, e para não baixar várias vezes as mesmas coisas.
	$ultNSU = $state->ultNSU;
	$ultNSU = (!isset($ultNSU) || is_null($ultNSU)) ? 0 : $ultNSU;

	$tools = new Tools($configJson, Certificate::readPfx($pfxcontent, $password));

	//só funciona para o modelo 55
	$tools->model('55');
	//este serviço somente opera em ambiente de produção
	$tools->setEnvironment(1);

	$maxNSU = $ultNSU;
	$iCount = 0;

	//executa a busca de DFe em loop
	while ($ultNSU <= $maxNSU) {
		$iCount++;

		if ($iCount >= $loopLimit) {
			break;
		}

		try {
			//executa a busca pelos documentos
			$resp = $tools->sefazDistDFe($ultNSU);
		} catch (\Exception $e) {
			echo $e->getMessage();
			break;
		}
	
		//extrair e salvar os retornos
		$dom = new \DOMDocument();
		$dom->loadXML($resp);
		$node = $dom->getElementsByTagName('retDistDFeInt')->item(0);
		$tpAmb = $node->getElementsByTagName('tpAmb')->item(0)->nodeValue;
		$verAplic = $node->getElementsByTagName('verAplic')->item(0)->nodeValue;
		$cStat = $node->getElementsByTagName('cStat')->item(0)->nodeValue;
		$xMotivo = $node->getElementsByTagName('xMotivo')->item(0)->nodeValue;
		$dhResp = $node->getElementsByTagName('dhResp')->item(0)->nodeValue;
		$ultNSU = $node->getElementsByTagName('ultNSU')->item(0)->nodeValue;
		$maxNSU = $node->getElementsByTagName('maxNSU')->item(0)->nodeValue;
		$lote = $node->getElementsByTagName('loteDistDFeInt')->item(0);

		if (empty($lote)) {
			//lote vazio
			continue;
		}

		//essas tags irão conter os documentos zipados
		$docs = $lote->getElementsByTagName('docZip');

		foreach ($docs as $doc) {
			$numnsu = $doc->getAttribute('NSU');
			$schema = $doc->getAttribute('schema');

			//descompacta o documento e recupera o XML original
			$content = gzdecode(base64_decode($doc->nodeValue));
			//identifica o tipo de documento
			$tipo = substr($schema, 0, 6);

			$docName = $numnsu . '-' . $tipo . '.xml';
			file_put_contents($currentDir . '/' . $docName, $content);
		}
			
		$state->ultNSU = $ultNSU;
		$stateJson = json_encode($state);
		file_put_contents($currentStateDir . '/state.json', $stateJson);

		sleep(2);
	}
}
?>