# Conexão do Site com o App Loteria

## Análise da Estrutura Atual

Baseado na estrutura de arquivos do projeto, identificamos que:

1. Existe uma pasta `api` com a estrutura básica para uma API RESTful
2. A API possui rotas para login e teste
3. Existe uma pasta específica para rotas de revendedor

## Requisitos para Conexão com o App

Para conectar o site com o App Loteria, precisamos implementar:

1. **Autenticação e Autorização**:
   - Endpoint de login seguro com JWT ou similar
   - Validação de tokens
   - Controle de acesso baseado em perfis (apostador, revendedor, admin)

2. **Endpoints para Apostas**:
   - Criar apostas
   - Consultar apostas
   - Verificar resultados

3. **Endpoints para Usuários**:
   - Registro de usuários
   - Atualização de perfil
   - Recuperação de senha

4. **Endpoints para Jogos**:
   - Listar jogos disponíveis
   - Obter detalhes de um jogo
   - Verificar resultados de jogos

5. **Endpoints para Transações**:
   - Histórico de transações
   - Depósitos e saques (se aplicável)
   - Comissões para revendedores

## Próximos Passos

1. **Documentação da API**:
   - Criar documentação completa dos endpoints
   - Definir formatos de requisição e resposta
   - Documentar códigos de erro

2. **Implementação dos Endpoints**:
   - Desenvolver os endpoints faltantes
   - Implementar validação de dados
   - Adicionar tratamento de erros

3. **Segurança**:
   - Implementar HTTPS
   - Configurar CORS para permitir apenas origens confiáveis
   - Implementar rate limiting para prevenir abusos

4. **Testes**:
   - Criar testes unitários para cada endpoint
   - Realizar testes de integração
   - Testar a conexão com o App

5. **Documentação para Desenvolvedores do App**:
   - Fornecer documentação clara para os desenvolvedores do App
   - Incluir exemplos de código
   - Fornecer ambiente de teste

## Configuração CORS

Para permitir que o App se comunique com a API, é necessário configurar o CORS corretamente. Adicione o seguinte código ao arquivo `.htaccess` na pasta `api`:

```
# Permitir CORS
Header set Access-Control-Allow-Origin "*"
Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header set Access-Control-Allow-Headers "Content-Type, Authorization"
```

Ou implemente via PHP no arquivo `index.php` da API:

```php
// Configuração CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Responder imediatamente às requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
```

## Exemplo de Endpoint para o App

Aqui está um exemplo de como um endpoint para listar jogos disponíveis poderia ser implementado:

```php
// api/routes/jogos.php
<?php
// Listar jogos disponíveis
$app->get('/jogos', function($request, $response) {
    // Conectar ao banco de dados
    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    // Consultar jogos disponíveis
    $query = "SELECT * FROM jogos WHERE status = 'ativo'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    // Formatar resposta
    $jogos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $jogos[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'descricao' => $row['descricao'],
            'preco' => $row['preco'],
            'data_sorteio' => $row['data_sorteio']
        ];
    }
    
    // Retornar resposta
    return $response->withJson([
        'status' => 'success',
        'data' => $jogos
    ]);
});
``` 