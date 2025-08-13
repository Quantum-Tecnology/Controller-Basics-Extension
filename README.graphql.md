# Consultas "GraphQL-like" via REST com Controller-Basics-Extension

Este documento descreve um padrão de uso do pacote para expor consultas ricas (aninhamento de relações, filtros, paginação por relação, contagens, etc.) em uma REST API, inspirando-se no estilo do GraphQL. Todo o conteúdo aqui foi elaborado a partir dos testes localizados em `tests/Feature` e `tests/Unit` deste repositório.

## Visão Geral

A ideia é permitir que o cliente construa uma consulta declarativa usando:
- fields: para informar quais relações e sub‑relações devem ser carregadas (e contadas);
- filters: para filtrar o modelo raiz e/ou relações usando uma sintaxe compacta;
- paginação por relação: para definir `per_page` e `page` em cada relação, inclusive aninhadas.

Internamente, o pacote utiliza:
- BuilderQuery: monta a consulta do Eloquent com includes, withCount, filtros e limites por relação;
- FilterSupport: faz o parse da sintaxe de filtros em um formato estruturado;
- PaginationSupport: converte parâmetros de paginação em estrutura aninhada e aplica limites seguros.

Assim, é possível, por exemplo, buscar um Post com seu Author, Comments e Likes de Comments, filtrando Comments, paginando por relação e retornando contagens — tudo via REST.

## Exemplo mínimo de uso

Objetivo: retornar um Post com seu Author carregado (sem filtros nem paginação).

- Requisição simples (GET):
  - `GET /posts/123?fields[author][]=id`
  - curl: `curl -X GET "https://sua-api.test/posts/123?fields[author][]=id"`

- Controller/Service (Laravel, conceitual):
  -
    ```php
    use Illuminate\Http\Request;
    use App\Models\Post;
    use QuantumTecnology\ControllerBasicsExtension\Builder\BuilderQuery;

    class PostController
    {
        public function show(Post $post, Request $request, BuilderQuery $builder)
        {
            $fields     = (array) $request->input('fields', []);   // quais relações incluir
            $filters    = (array) $request->input('filters', []);  // filtros opcionais
            $pagination = [];                                      // opcional para um exemplo mínimo

            $query = $builder->execute(Post::query()->getModel(), $fields, $filters, $pagination);

            return $query->whereKey($post->id)->firstOrFail();
        }
    }
    ```

Resultado: o JSON do Post 123 com a relação `author` incluída e o campo `author` presente no payload. Para relações diretas listadas em `fields`, o pacote também calcula `*_count` quando aplicável.

## fields: selecionando relações (e contagens)

O parâmetro `fields` é uma estrutura em árvore que descreve quais relações devem ser incluídas. Somente chaves com valor array são consideradas relações. Exemplos (em PHP, como vem dos testes):

- Simples, com relações e sub‑relações:
  - `['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]]`
  - Inclui: `author`, `comments` e `comments.likes`.
- Profundamente aninhado, apenas para ilustrar o comportamento:
  - `['a' => ['b' => ['c' => ['d' => ['id']]]]]`
  - Inclui: `a`, `a.b`, `a.b.c`, `a.b.c.d`.

Com base nisso, o BuilderQuery:
- Adiciona withCount para cada relação direta listada em `fields` (por exemplo, `comments_count` para `comments`), e também pode adicionar withCount para sub‑relações, se presentes;
- Aplica includes (`with`) nas relações em notação camelCase quando necessário;
- Para relações do tipo BelongsTo, não aplica paginação (faz sentido, pois retorna no máx. 1 registro);
- Para as demais (HasMany/MorphMany/BelongsToMany/MorphToMany), aplica paginação por relação se você fornecer `per_page` e `page` (veja a seção de paginação).

Como enviar em REST:
- JSON (corpo da requisição): `fields` como objeto/array aninhado.
- Query string: `fields[comments][likes][]=id` etc. (Laravel converte em array).

## filters: filtrando raiz e relações

A sintaxe dos filtros segue o padrão: `relation_path(campo,operador)` como a chave; o valor pode ser único, lista (array), ou string separada por vírgula/pipe. Exemplos tirados dos testes:

- Filtrar relação filha por operador customizado:
  - Chave: `comments(id,<=)` Valor: `20`
  - Interpretação: em `comments`, `id <= 20`.
- Igualdade (operador padrão `=` se omitido):
  - Chave: `comments(id)` Valor: `3`
  - Interpretação: em `comments`, `id = 3`.
- Filtro no modelo raiz (sem relação):
  - Chave: `(is_draft)` Valor: `true` ou `false` (strings serão convertidas para boolean)
  - Interpretação: no modelo raiz, `is_draft = true|false`.
- Busca textual em múltiplas colunas com "byFilter":
  - Chave: `(byFilter,title)` Valor: `testing`
  - Interpretação: cria um where que faz OR com LIKE em `title`, buscando por prefixo (ex.: `title LIKE 'testing%'`).
  - Dica: você pode combinar múltiplas colunas separando no operador: `(byFilter,title;subtitle)` com Valor: `foo`. Isso aplica OR em `title` e `subtitle`.
- Scopes/filters customizados começando com "by":
  - Se a chave começar com `by`, o pacote chamará o método correspondente no query builder (ex.: `(bySomething)` chama `$query->bySomething(...)`). O valor é repassado como `Collection`.
- Valores múltiplos:
  - `user(id)` com valor `1,2,3` ou `1|2|3` vira `IN (1,2,3)`.

Notas:
- Valores `"true"`/`"false"` (strings) são convertidos para bool automaticamente.
- Para operador `like`, o pacote constrói internamente um OR com `orWhereLike` em cada item informado.
- Para o modelo raiz, utilize a sintaxe sem relação: `(campo,operador)`.

Como enviar em REST:
- Query string, por exemplo: `?comments(id,%3C%3D)=20&(is_draft)=true`.
- JSON (corpo): um objeto no formato chave/valor, por ex.:
  - `{ "comments(id,<=)": 20, "(is_draft)": "true" }`.

## Paginação por relação

Você pode definir `per_page` e `page` individualmente por relação (e sub‑relação), via parâmetros com o seguinte formato:
- `per_page_<path>` e `page_<path>`
- O `<path>` aceita pontos para navegação em profundidade (ex.: `users.posts.comments`).

Exemplos de query string (dos testes de unidade):
- `per_page_users=10&page_users=1`
- `per_page_users.posts=5&page_users.posts=2`
- `per_page_users.posts.comments=20&page_users.posts.comments=4`

Comportamento e limites:
- Se `per_page` não for informado para uma relação, usa `config('page.per_page')`.
- Se `per_page` exceder `config('page.max_page')`, o valor será limitado ao máximo e registrado via LogSupport.
- Se após todas as checagens `per_page` ficar "falsy", cai para `1`.
- Para relações BelongsTo não há paginação (é ignorada).

## Exemplos práticos

1) Post com Author e Comments paginados, incluindo Likes dos Comments:
- fields:
  - `{"author": ["id"], "comments": ["id", "likes": ["id"]]}`
- paginação (query string):
  - `per_page_comments=5&page_comments=2`
- filtros (opcionais):
  - `comments(id,<=)=20` ou `(is_draft)=true`

Requisição (exemplos):
- GET com query string e fields em query:
  - `GET /posts/123?per_page_comments=5&page_comments=2&comments(id,%3C%3D)=20&fields[author][]=id&fields[comments][]=id&fields[comments][likes][]=id`
- POST/GET com body JSON (quando seu endpoint aceitar):
  - Body:
    - `{ "fields": { "author": ["id"], "comments": ["id", { "likes": ["id"] }] }, "filters": { "comments(id,<=)": 20 }, "pagination": { "per_page_comments": 5, "page_comments": 2 } }`

Resultado esperado (conceitual):
- O Post 123 virá com:
  - `author` carregado;
  - `comments` da página 2 com `per_page` 5;
  - `comments_count` (contagem total de comments do post) calculado;
  - `likes` carregados para cada comment, possivelmente com `likes_count` quando solicitado.

2) Busca textual no título dos Posts:
- filtros: `(byFilter,title)=testing`
- Se existir um Post com título começando com `testing_...`, ele será retornado. Se não, resultado vazio. Isso está espelhado nos testes em `BuilderQueryPostCommentsTest`.

3) Filtrar por boolean no modelo raiz:
- filtros: `(is_draft)=true` retorna somente registros com `is_draft` verdadeiro; `(is_draft)=false` filtra pelos falsos. As strings são convertidas para boolean automaticamente.

## Integração com Controllers/Services (Laravel)

- O pacote oferece traits e services que facilitam a montagem de respostas REST. Você pode, por exemplo, dentro do seu Service/Controller, coletar `fields`, `filters` e parâmetros de paginação do Request e repassá‑los ao `BuilderQuery`.
- Exemplo conceitual (Service):
  - `$query = app(BuilderQuery::class)->execute(Post::query()->getModel(), $fields, $filters, $pagination);`
  - `return $query->where('id', $id)->sole();`
- Ao usar os traits/serviços base (veja README principal), você pode combinar com este padrão para expor endpoints flexíveis e consistentes.

### Exemplo usando a trait AsGraphQLController

A trait AsGraphQLController expõe automaticamente as ações de CRUD (index, show, store, update, destroy) já integradas ao padrão GraphQL-like deste documento. Basta definir o model() e, opcionalmente, allowedFields().

- Controller mínimo:
  
  ```php
  use Illuminate\Database\Eloquent\Model;
  use App\Models\Post;
  use QuantumTecnology\ControllerBasicsExtension\Traits\AsGraphQLController;
  
  final class PostController
  {
      use AsGraphQLController;
  
      // Define o Model base
      protected function model(): Model
      {
          return new Post();
      }
  
      // Opcional: restringe os fields que podem ser solicitados
      protected function allowedFields(): array
      {
          return [
              'id',
              'title',
              'comments' => [
                  'id',
                  'likes' => ['id'],
              ],
          ];
      }
  }
  ```

- Rotas (exemplos Laravel):
  - `GET /posts` → index()
  - `GET /posts/{post}` → show()
  - `POST /posts` → store()
  - `PUT/PATCH /posts/{post}` → update()
  - `DELETE /posts/{post}` → destroy()

- Como enviar parâmetros com a trait:
  - fields: igual às demais seções, via query string (ex.: `fields[comments][likes][]=id`) ou JSON.
  - filters: prefixe as chaves com `filter_` seguindo a sintaxe deste guia. Exemplos:
    - `?filter_(is_draft)=false` (filtro no modelo raiz)
    - `?filter_comments(id,%3C%3D)=20` (filtro na relação comments, `id <= 20`)
    - Parâmetros de rota também viram filtros automaticamente. Ex.: em `GET /posts/123`, o trait aplica `(id)=123` internamente.
  - Paginação por relação: use `per_page_<path>` e `page_<path>`, como em `?per_page_comments=5&page_comments=2`.

- Respostas:
  - index(): retorna JSON paginado pelo GraphQlService, incluindo relações, withCount e paginação por relação.
  - show(): retorna o recurso único formatado pelo GraphQlService.
  - store/update/destroy: validam dados via Requests convencionados pelo caminho do Controller (ver README principal) e persistem relações via RelationshipService.

## Dicas e cuidados

- Sempre valide/normalize `fields`, `filters` e paginação no Request (ex.: colunas permitidas, relações válidas), para evitar abuso.
- Combine com políticas de autorização ao incluir relações sensíveis.
- Ajuste `page.per_page` e `page.max_page` nas configs do seu app para limites adequados.
- Use `byFilter` para buscas simples em múltiplas colunas, e crie métodos `by...` no seu model para lógicas de filtro específicas.

## Referências de testes usados como base

- `tests/Feature/Builder/BuilderQueryPostCommentsTest.php`
- `tests/Feature/Support/PaginationSupportTest.php`
- `tests/Unit/Support/FilterSupportTest.php`
- `tests/Unit/Support/PaginationSupportTest.php`
- `tests/Unit/Builder/BuilderQueryTest.php`

Esses testes demonstram a sintaxe, o parse e o comportamento esperado que você pode reproduzir na sua API REST para obter uma experiência "GraphQL-like" sem abandonar o padrão REST.
