<h3 align="center">Controller-Basics-Extension for Laravel</h3>

## 🧐 Sobre <a name = "about"></a>
Este pacote inclui uma classe de extensão desacoplada que contem os principais metodos para um crud dentro de controller:
  - index;
  - show;
  - store;
  - update;
  - destroy;

Sempre que possivel ele sera atualizado, e esta aberto para a comunidade sugerir melhorias.

## 🏁 Para utilizar o pack

Para utilizar a classe, basta instalar ela utilizando o comando do composer:

```
composer require quantumtecnology/controller-basics-extension
```

extender ela na sua classe de controller dentro de app/controllers e com isso, o seu crud ja vai estar finalizado e pronto para uso.

Pronto, ja é para estar funcionando.

## 🎈 Recursos

Nele existem algumas ferramentas uteis.

- BaseController:
  - Um index que lista todo o conteudo dentro da model inforada.
  - Um show que apresenta o id informado e caso de falha, apresenta uma exception que pode ser customizada.
  - Utilizando o pacote [Validate-Trait](https://packagist.org/packages/quantumtecnology/validate-trait) o store captura os parametros validados e persiste no banco de dados.
  - Utilizando o pacote [Validate-Trait](https://packagist.org/packages/quantumtecnology/validate-trait) o update captura os parametros validados e atualiza no banco de dados.
  - E um destroy que remove do banco de dados.

## 🔐 Criptografia de IDs (Hashids)

O `CryptographyMiddleware` esconde os ids inteiros da API: na resposta, `"id": 42` sai como `"id": "v5rNVenmKP"`, e na requisição o hash volta a ser `42` antes de chegar ao controller. Depende do [vinkla/hashids](https://github.com/vinkla/laravel-hashids):

```
composer require vinkla/hashids
```

Aplique o middleware nas rotas que devem ter os ids criptografados:

```php
Route::middleware(\QuantumTecnology\ControllerBasicsExtension\Middleware\CryptographyMiddleware::class)
    ->group(base_path('routes/api.php'));
```

### O que é decodificado e codificado

O middleware escolhe os campos pelo **nome**, usando o `regex` (padrão `/id$|_id$|Id$|_ids$/`):

| Onde | Na requisição (hash → inteiro) | Na resposta (inteiro → hash) |
|---|---|---|
| Parâmetro de rota (`{id}`, `{post_id}`) | ✅ | — |
| Query e corpo (`user_id`, `plan_ids[]`, `?filter[store_id]=`) | ✅ | — |
| Cabeçalhos (`X-Admin`, `X-Agent`, `X-User`) | ✅ | — |
| Chaves do JSON (`id`, `user_id`...) | — | ✅ (só valores inteiros) |

Um valor que não decodifica responde **400** ("Identificador inválido"), e um parâmetro de rota fora do alfabeto responde **404**.

### Configuração

| Variável | Chave | Padrão | Para quê |
|---|---|---|---|
| `ENABLE_CRYPTOGRAPHY` | `hashids.enable_cryptography` | `false` | Liga o middleware. Desligado, as rotas recebem e devolvem os ids crus. |
| `HASHID_REGEX` | `hashids.regex` | `/id$\|_id$\|Id$\|_ids$/` | Quais **nomes** de campo são ids. |
| `HASHID_HEADER_REGEX` | `hashids.headers.regex` | `/^(X-Admin\|X-Agent\|X-User)/i` | Quais cabeçalhos carregam ids. |
| `HASHID_IGNORE_UUID` | `hashids.ignore_uuid` | `false` | Deixa passar intactos os valores com formato de UUID (ver abaixo). |
| `HASHID_DEFAULT` | `hashids.default` | `main` | Conexão do Hashids (`salt`, `length`, `alphabet` em `hashids.connections`). |

> 🧪 Fora de produção, `?crypt=true` liga a criptografia numa requisição, mesmo com `ENABLE_CRYPTOGRAPHY=false`. Use em teste para exercitar o mesmo caminho de produção.

### Projetos que misturam id inteiro e UUID

O `regex` olha o **nome** do campo, nunca o valor. Numa tabela de chave UUID, o id também chega em `{id}` ou `*_id`, e o middleware tenta decodificá-lo como hash: a rota responde 404 ou 400, e **só com a criptografia ligada** (normalmente só em produção, então os testes passam e o erro aparece depois do deploy).

Ligue o `HASHID_IGNORE_UUID=true` nesse caso. Valores com formato de UUID passam intactos; hashids continuam sendo decodificados, inclusive numa lista mista:

```
GET /coupons/0199a3f2-7c1e-7b3a-9d4e-1f2a3b4c5d6e        → $id = '0199a3f2-...' (intacto)
GET /coupons/v5rNVenmKP                                   → $id = 42
GET /items?coupon_ids[]=v5rNVenmKP&coupon_ids[]=0199a3f2-... → [42, '0199a3f2-...']
```

Não há colisão: o alfabeto do Hashids não tem hífen, e o UUID tem (`8-4-4-4-12`). Um valor que não é nem hash nem UUID continua sendo recusado. Na resposta nada muda: o UUID já não era codificado, porque só valores inteiros passam pelo Hashids.

## 🧐 Outras Bibliotecas

- [Enum-Basics-Extension](https://packagist.org/packages/quantumtecnology/enum-basics-extension) - Utilizado para auxiliar nas Classes de Enums;
- [SetSchema-Trait](https://packagist.org/packages/quantumtecnology/setschema-trait-postgresql) - Suprir a necessidade de setar os schemas automaticamente do PostgreSQL;
- [Validate-Trait](https://packagist.org/packages/quantumtecnology/validate-trait) - Bindar os Requests automaticamente de acordo com o caminho do Controller Pattern;
- [PerPage-Trait](https://packagist.org/packages/quantumtecnology/perpage-trait) - Padronizar a quantidade do paginate na api inteira e definir uma quantidade máxima;
- [Handler-Basics-Extension](https://packagist.org/packages/quantumtecnology/handler-basics-extension) - Contem tratamento das principais exceções do laravel, e contem varios responses para lhe auxiliar;

## 📚 Documentação GraphQL-like

- [GraphQL-like via REST (README.graphql.md)](README.graphql.md) — Guia baseado nos testes, mostrando fields, filters e paginação por relação.

## ⛏️ Ferramentas

- [php](https://www.php.net/) - linguagem
- [laravel](https://laravel.com/) - framework

## ✍️ Autor

- [@Luis Gustavo Santarosa Pinto](https://github.com/QuantumTecnology) - Idea & Initial work
