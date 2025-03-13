# Loteria - Sistema de Loteria Online

Este é o repositório do sistema de loteria online, incluindo o site web e a API para o aplicativo Android.

## Configuração do Deploy Automático

Este projeto está configurado para deploy automático usando webhooks do GitHub. Quando você faz um push para a branch principal, as alterações são automaticamente implantadas no servidor.

### Passos para Configurar o Deploy Automático

1. **Configurar o Repositório no GitHub**
   - Crie um repositório no GitHub
   - Adicione o código do projeto ao repositório
   - Faça o primeiro push para a branch principal

2. **Configurar o Servidor**
   - Certifique-se de que o Git está instalado no servidor
   - Clone o repositório no diretório web do APanel
   - Configure as permissões corretas para os arquivos e diretórios

3. **Configurar o Webhook no GitHub**
   - Vá para as configurações do repositório no GitHub
   - Clique em "Webhooks" > "Add webhook"
   - Configure o webhook:
     - Payload URL: `https://seu-dominio.com/deploy.php`
     - Content type: `application/json`
     - Secret: A mesma chave secreta definida no arquivo `deploy.php`
     - Eventos: Selecione "Just the push event"
     - Ative a opção "Active"

4. **Testar o Deploy Automático**
   - Faça uma pequena alteração no código
   - Faça commit e push para o GitHub
   - Verifique o arquivo `deploy_log.txt` no servidor para confirmar que o deploy foi bem-sucedido

## Estrutura do Projeto

- `/api` - API para o aplicativo Android
- `/admin` - Painel de administração
- `/revendedor` - Painel do revendedor
- `/apostador` - Interface do apostador
- `/config` - Arquivos de configuração
- `/includes` - Arquivos de inclusão e funções auxiliares

## Desenvolvimento

### Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Composer para gerenciamento de dependências

### Instalação Local

1. Clone o repositório
2. Configure o banco de dados em `config/config.php`
3. Importe o arquivo SQL `loteria.sql`
4. Acesse o projeto pelo navegador

## Aplicativo Android

O aplicativo Android está disponível no repositório separado: [appLoteria](https://github.com/seu-usuario/appLoteria) 