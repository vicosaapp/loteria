# Sistema de Manutenção Centralizado

## Visão Geral

O Sistema de Manutenção Centralizado foi desenvolvido para facilitar a gestão do modo de manutenção em toda a plataforma de loteria. A nova implementação elimina a duplicação de código e centraliza toda a lógica relacionada à manutenção em um único arquivo.

## Arquivos Principais

- `includes/manutencao_handler.php`: Arquivo central que contém todas as funções relacionadas ao modo de manutenção.
- `includes/verificar_manutencao.php`: Script simplificado que utiliza as funções do manipulador central.
- `manutencao.php`: Página principal de manutenção.
- `revendedor/manutencao.php`: Página de manutenção específica para revendedores.
- `apostador/manutencao.php`: Página de manutenção específica para apostadores.
- `admin/manutencao.php`: Página de manutenção específica para administradores.

## Funções Principais

O sistema centralizado fornece as seguintes funções:

### 1. `carregarDatabaseConfig()`
Gerencia o carregamento do arquivo de configuração do banco de dados, tentando vários caminhos para garantir a compatibilidade em diferentes ambientes e estruturas de diretórios.

### 2. `verificarModoManutencao()`
Verifica se o modo de manutenção está ativo. Retorna `true` se estiver ativo e `false` se estiver desativado ou se o usuário for um administrador.

### 3. `obterMensagemManutencao()`
Obtém a mensagem personalizada de manutenção do banco de dados. Se não houver mensagem configurada, retorna a mensagem padrão.

### 4. `exibirPaginaManutencao($area = 'geral')`
Renderiza a página de manutenção com base na área especificada (geral, apostador, revendedor ou admin). Esta função lida com todas as diferenças visuais e de configuração para cada área do sistema.

### 5. `verificarStatusManutencaoAjax()`
Processa requisições AJAX para verificar o status do modo de manutenção. Retorna dados no formato JSON.

## Como Funciona

1. A verificação de manutenção é feita incluindo o arquivo `includes/verificar_manutencao.php` no início das páginas que precisam ser protegidas.
2. O arquivo `verificar_manutencao.php` detecta em qual área do sistema está sendo executado e redireciona para a página de manutenção específica da área.
3. A página de manutenção verifica periodicamente o status do modo de manutenção via AJAX para redirecionar o usuário de volta à página principal quando o modo de manutenção for desativado.

## Configuração do Banco de Dados

O modo de manutenção é controlado por duas colunas na tabela `configuracoes`:

- `modo_manutencao`: 0 = desativado, 1 = ativado
- `mensagem_manutencao`: Mensagem personalizada exibida durante a manutenção

## Vantagens da Nova Implementação

1. **Redução de Duplicação**: Elimina código duplicado entre as diversas páginas de manutenção.
2. **Manutenção Simplificada**: Alterações na lógica ou no design da página de manutenção só precisam ser feitas em um único lugar.
3. **Consistência**: Garante que todas as áreas do sistema exibam informações consistentes durante a manutenção.
4. **Escalabilidade**: Facilita a adição de novas funcionalidades relacionadas à manutenção no futuro.
5. **Redirecionamento Específico**: Detecta automaticamente a área do sistema e redireciona para a página de manutenção apropriada.
6. **Gerenciamento de Sessões**: Verifica se a sessão já foi iniciada antes de tentar iniciar uma nova, evitando avisos.
7. **Caminhos Flexíveis**: Usa estratégias robustas para localizar arquivos, independentemente da configuração do servidor.

## Implementações Futuras Sugeridas

1. **Manutenção Programada**: Adicionar funcionalidade para agendar períodos de manutenção.
2. **Manutenção Seletiva**: Permitir que apenas partes específicas do sistema entrem em manutenção.
3. **Logs de Manutenção**: Registrar quando o sistema entra e sai do modo de manutenção, e qual administrador efetuou a mudança.
4. **Notificações**: Enviar notificações aos usuários sobre manutenções programadas.

## Resolução de Problemas

Se uma área do sistema não estiver redirecionando corretamente para a página de manutenção:

1. Verifique se o arquivo `includes/verificar_manutencao.php` está sendo incluído no topo da página principal da área.
2. Verifique os logs para mensagens com o prefixo `[Manutenção]` para identificar qualquer erro.
3. Assegure-se de que o padrão de URL no `verificar_manutencao.php` esteja detectando corretamente a área. 