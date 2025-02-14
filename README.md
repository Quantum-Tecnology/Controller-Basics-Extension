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
composer require gustavosantarosa/controller-basics-extension
```

extender ela na sua classe de controller dentro de app/controllers e com isso, o seu crud ja vai estar finalizado e pronto para uso.

Pronto, ja é para estar funcionando.

## 🎈 Recursos

Nele existem algumas ferramentas uteis.

- BaseController:
  - Um index que lista todo o conteudo dentro da model inforada.
  - Um show que apresenta o id informado e caso de falha, apresenta uma exception que pode ser customizada.
  - Utilizando o pacote [Validate-Trait](https://packagist.org/packages/gustavosantarosa/validate-trait) o store captura os parametros validados e persiste no banco de dados.
  - Utilizando o pacote [Validate-Trait](https://packagist.org/packages/gustavosantarosa/validate-trait) o update captura os parametros validados e atualiza no banco de dados.
  - E um destroy que remove do banco de dados.

## 🧐 Outras Bibliotecas

- [Enum-Basics-Extension](https://packagist.org/packages/gustavosantarosa/enum-basics-extension) - Utilizado para auxiliar nas Classes de Enums;
- [SetSchema-Trait](https://packagist.org/packages/gustavosantarosa/setschema-trait-postgresql) - Suprir a necessidade de setar os schemas automaticamente do PostgreSQL;
- [Validate-Trait](https://packagist.org/packages/gustavosantarosa/validate-trait) - Bindar os Requests automaticamente de acordo com o caminho do Controller Pattern;
- [PerPage-Trait](https://packagist.org/packages/gustavosantarosa/perpage-trait) - Padronizar a quantidade do paginate na api inteira e definir uma quantidade máxima;
- [Handler-Basics-Extension](https://packagist.org/packages/gustavosantarosa/handler-basics-extension) - Contem tratamento das principais exceções do laravel, e contem varios responses para lhe auxiliar;


## ⛏️ Ferramentas

- [php](https://www.php.net/) - linguagem
- [laravel](https://laravel.com/) - framework

## ✍️ Autor

- [@Luis Gustavo Santarosa Pinto](https://github.com/GustavoSantarosa) - Idea & Initial work
