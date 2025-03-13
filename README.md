# Loteria - Sistema de Loteria Online

Este é o repositório do sistema de loteria online, incluindo o site web e a API para o aplicativo Android.

## Configuração do Deploy Automático

Este projeto está configurado para fazer deploy automático para o servidor sempre que houver um push para o branch `main` no GitHub.

### Configuração do GitHub Actions

1. **Acesse seu repositório no GitHub**
2. **Vá para "Settings" > "Secrets" > "Actions" > "New repository secret"**
3. **Adicione um novo segredo**:
   - Nome: `FTP_PASSWORD`
   - Valor: `patto200`
4. **Clique em "Add secret"**

### Como Funciona o Deploy

1. Quando você faz um push para o branch `main`, o GitHub Actions é acionado automaticamente
2. O GitHub Actions faz o checkout do código
3. O GitHub Actions envia os arquivos para o servidor via FTP
4. Os arquivos são atualizados no servidor

### Arquivos Excluídos do Deploy

Os seguintes arquivos/diretórios não são enviados para o servidor:
- Arquivos e diretórios do Git (`.git`, `.github`, etc.)
- Diretório `node_modules`
- Arquivo `deploy.php`
- Arquivo `deploy_log.txt`

### Fluxo de Trabalho de Desenvolvimento

1. **Desenvolva localmente** no Cursor IDE
2. **Faça commit e push para o GitHub**:
   ```bash
   git add .
   git commit -m "Descrição das alterações"
   git push origin main
   ```
3. **O GitHub Actions fará o deploy automaticamente**
4. **Verifique o status do deploy** na aba "Actions" do GitHub

### Solução de Problemas

Se o deploy não estiver funcionando:
1. Verifique se o segredo `FTP_PASSWORD` está configurado corretamente
2. Verifique os logs na aba "Actions" do GitHub
3. Verifique se o servidor FTP está aceitando conexões
4. Verifique se o caminho remoto está correto

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