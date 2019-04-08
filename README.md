# Sistema para obtenção de Documentos Fiscais (PHP)

## 1) Configurações de ambiente

Informar em um arquivo `env.json` no diretório `/config` (no bucket do S3) os dados de configurações (ver `env.sample.json`):

`tpAmb`: 1 = produção; 2 = homologação

`clients`: Array com os ids dos clientes (os ids são as chaves no arquivo `clients.json`, ver `clients.sample.json`)

## 2) Configurações de clientes

Informar em um arquivo `clients.json` no diretório `/config` (no bucket do S3) os dados de configurações (ver `clients.sample.json`):

`razaosocial`: Nome completo do usuário (Ex: `RAZAO SOCIAL DO EMISSOR`)

`cnpj`: Número do CNPJ do usuário (Ex: `99999999999999`)

`ie`: Inscrição Estadual? (Ex: `999999999999`)

`siglaUF`: Unidade Federativa (Ex: `SP`)

`CSC`: Token de segurança fornecido pela SEFAZ para NFCe (Ex: `GPB0JBWLUR6HWFTVEAS6RJ69GPCROFPBBB8G`)

`CSCid`: Id do Token de segurança fornecido pela SEFAZ para NFCe (Ex: `000002`)