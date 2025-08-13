# CHANGE LOG

## V3.0.0 (13/08/2025)

# PT-BR

- Adicionada documentação README.graphql.md descrevendo o padrão "GraphQL-like" via REST (fields, filters, paginação por relação), baseada nos testes em tests/Feature e tests/Unit. Inclui exemplos práticos e instruções de uso.
- Sem mudanças de API; atualização de documentação.

# EN

- Added README.graphql.md documenting the "GraphQL-like" pattern over REST (fields, filters, per-relation pagination), based on tests in tests/Feature and tests/Unit. Includes practical examples and usage guidance.
- No API changes; docs update.

## 2.7.12

# PT-BR

- Migrado o service interface para o pacote service, pois se mantivesse aqui, a dependencia seria no duplo sentido, com ele la, somente o controller depende do service, e nao o contrario. @GustavoSantarosa

# EN

- Migrated the service interface to the service package, as keeping it here would create a two-way dependency; with it there, only the controller depends on the service, not the other way around. @GustavoSantarosa

## 2.7.12 (08/08/2025)

# PT-BR

- Apresentar ServiceInterface e atualizar dicas de tipo de serviço em BootControllerTrait ~~e ShowControllerTrait.~~ <span style="color: #d73a49;">(Migrado para a versao 3)</span> @bhcosta90

# EN

- Introduce ServiceInterface and update service type hints in BootControllerTrait ~~and ShowControllerTrait.~~ <span style="color: #d73a49;">(Migrated to version 3)</span> @bhcosta90

## 2.7.11 (06/08/2025)

# PT-BR

- Adicionado um parametro que desabilita a criptografia quando o ambiente é diferente de producao.

# EN

- Added a parameter that disables encryption when the environment is not production.

## V2.7.10 (05/07/2025)

# PT-BR

- Corrigido um problema que ocorria no decoder, ao efetuar pregmatch, e era um array, estava tendo erro de tipagem. @GustavoSantarosa

# EN

- Fixed an issue that occurred in the decoder when performing pregmatch, and it was an array, there was a type error. @GustavoSantarosa

## V2.7.9 (13/06/2025)

# PT-BR

- Feito um ajuste na bindAttributesTrait para lidar com atributos que não são preenchíveis. @bhcosta90

# EN

- Made an adjustment in the BindAttributesTrait to handle attributes that are not fillable. @bhcosta90

## V2.7.8 (05/06/2025)

# PT-BR

- Corrigido um problema que ocorria no decoder, quando havia varios parametros separados por virgula, o decoder nao estava concluindo. @GustavoSantarosa

# EN

- Fixed an issue that occurred in the decoder when there were multiple parameters separated by commas, the decoder was not completing. @GustavoSantarosa

## V2.7.7 (03/06/2025)

# PT-BR

- Aprimorar o bindattributestestrait para se ligar condicionalmente atributos com base em campos preenchidos. @bhcosta90 in https://github.com/Quantum-Tecnology/Controller-Basics-Extension/pull/14

# EN

- Enhance BindAttributesTrait to conditionally bind attributes based on fillable fields. @bhcosta90 in https://github.com/Quantum-Tecnology/Controller-Basics-Extension/pull/14

## V2.7.6 (03/06/2025)

# PT-BR

- Melhorar a lógica de ligação de atributos para excluir campos especificados. @bhcosta90 in https://github.com/Quantum-Tecnology/Controller-Basics-Extension/pull/12

# EN

- Enhance attribute binding logic to exclude specified fields. @bhcosta90 in https://github.com/Quantum-Tecnology/Controller-Basics-Extension/pull/12

## V2.7.5 (02/06/2025)

# PT-BR

- condição em WhenLoadedTrait para verificar corretamente a contenção de relacionamento. @bhcosta90 in https://github.com/Quantum-Tecnology/Controller-Basics-Extension/pull/11

# EN

- condition in WhenLoadedTrait to correctly check relationship containment. @bhcosta90 in https://github.com/Quantum-Tecnology/Controller-Basics-Extension/pull/11

## V2.7.4 (26/05/2025)

# PT-BR

- Ajustado o decoder para quando for um array nao nomeado e existir algo. @GustavoSantarosa

# EN

- Adjusted the decoder for when it is an unnamed array and something exists. @GustavoSantarosa

## V2.7.3 (26/05/2025)

# PT-BR

- O hashid estava tentando decodificar null, e estava apresentando erro, corrigido, agora, ele só decodifica se for diferente de null. @GustavoSantarosa

# EN

- The hashid was trying to decode null and was throwing an error, fixed, now it only decodes if it is not null. @GustavoSantarosa

## V2.7.2 (24/05/2025)

# PT-BR

- Corrigindo um problema que ocorria no decoder, quando havia um array decodificavel de indice nao nominal. @GustavoSantarosa

# EN

- Fixing an issue that occurred in the decoder when there was a decodable array with a non-nominal index. @GustavoSantarosa

## V2.7.1 (21/05/2025)

# PT-BR

- Corrigindo a chave que busca o when loaded para includes, conforme o padrão. @bhcosta90 in https://github.com/Quantum-Tecnology/Controller-Basics-Extension/pull/8

# EN

- Fixing the key that searches for when loaded for includes, as per the standard. @bhcosta90 in https://github.com/Quantum-Tecnology/Controller-Basics-Extension/pull/8

## V2.7.0 (20/05/2025)

# PT-BR

- Corrigindo a validação do decoder para headers, params e body. @bhcosta90
- Atualizado as regras do phpcsixer para se adequar aos demais pacotes. @GustavoSantarosa
- O pacote, perdeu suporte para o php 8.2, agora somente para php 8.3 ou superior. @GustavoSantarosa

# EN

- Correcting Decoder Validation for Headers, Params and Body @bhcosta90
- Updated phpcsfixer rules to match other packages. @GustavoSantarosa
- Dropped support for PHP 8.2, now requires PHP 8.3 or higher. @GustavoSantarosa

## V2.6.2 (19/05/2025)

# PT-BR

- Fixado um ajuste no removebind de bind trait. @gustavosantarosa

# EN

- Fixed an adjustment in the removebind of the bind trait. @gustavosantarosa

## V2.6.1 (19/05/2025)

# PT-BR

- Ajustado um erro que estava ocorrendo na trait bind. @gustavosantarosa

# EN

- Fixed an error that was occurring in the bind trait. @gustavosantarosa

## V2.6.0 (16/05/2025)

# PT-BR

- Implementado uma trait para bind de atributos. @GustavoSantarosa

# EN

- Implemented a trait for attribute binding. @GustavoSantarosa

## V2.5.8 (12/05/2025)

# PT-BR

- Somente verificar a chave que esta identificada. @bhcosta90 https://github.com/Quantum-Tecnology/Controller-Basics-Extension/pull/6

# EN

- Only verify if the the key is identify. @bhcosta90 https://github.com/Quantum-Tecnology/Controller-Basics-Extension/pull/6

## V2.5.7 (06/05/2025)

# PT-BR

- Ajustado um erro, onde era permitido passar valores inteiros para o decoder. @GustavoSantarosa

# EN

- Fixed an error where it was allowed to pass integer values to the decoder. @GustavoSantarosa

## V2.5.6 (06/05/2025)

# PT-BR

- Acrescentado uma nova validação no decoder para quando o input é um array sem index. @GustavoSantarosa

# EN

- Added a new validation in the decoder for when the input is an array without an index. @GustavoSantarosa

## V2.5.5 (29/04/2025)

# PT-BR

- Ajustado os namespace das traits whenloaded e whenloadedfilter. @GustavoSantarosa

# EN

- Adjusted the namespaces of the whenloaded and whenloadedfilter traits. @GustavoSantarosa

## V2.5.4 (24/04/2025)

# PT-BR

- Ajustado alguns problemas para evitar dynamic properties. @GustavoSantarosa

# EN

- Adjusted some issues to avoid dynamic properties. @GustavoSantarosa

## V2.5.3 (24/04/2025)

# PT-BR

- Removido uma trait que nao deveria existir, pois é do pacote service. @GustavoSantarosa

# EN

- Removed a trait that shouldn't exist, as it belongs to the service package. @GustavoSantarosa

## V2.5.2 (24/04/2025)

# PT-BR

- Ao inves de verificar a comparação do item, vamos comparar a relação. @bhcosta90 https://github.com/Quantum-Tecnology/Controller-Basics-Extension/pull/5

# EN

- Instead of checking the item comparison, let's compare the relationship. @bhcosta90 https://github.com/Quantum-Tecnology/Controller-Basics-Extension/pull/5

## V2.5.1 (17/04/2025)

# PT-BR

- Implementado o filtersAllowed para ser startado. @GustavoSantarosa
- Feito Composer update. @GustavoSantarosa

# EN

- Implemented the filtersAllowed to be initialized. @GustavoSantarosa
- Performed Composer update. @GustavoSantarosa

## V2.5.0 (06/04/2025)

# PT-BR

- Atualizado o composer. @GustavoSantarosa
- Implementado o retorno por Data. @GustavoSantarosa
- Implementado o simple pagination. @GustavoSantarosa
- Implementado 2 novas traits para o crud, sendo elas: WhenLoadedFilterTrait e WhenLoadedTrait. @GustavoSantarosa

# EN

- Updated the composer. @GustavoSantarosa
- Implemented return by Date. @GustavoSantarosa
- Implemented simple pagination. @GustavoSantarosa
- Implemented 2 new traits for CRUD: WhenLoadedFilterTrait and WhenLoadedTrait. @GustavoSantarosa

## V2.4.1 (16/03/2025)

# PT-BR

- Realizado algumas correções e criado uma config de attributes para o hash validar alem do regex @GustavoSantarosa

# EN

- Implemented a new customizable configuration for headers and inputs using regex @GustavoSantarosa

## V2.4.0 (16/03/2025)

# PT-BR

- implementado uma nova configuração customizavel para headers e inputs por regex @GustavoSantarosa

# EN

- Implemented a new customizable configuration for headers and inputs using regex @GustavoSantarosa

## V2.3.2 (16/03/2025)

# PT-BR

- Introduzido suporte ao pacote vinkla/hashids:^11.0 para adequar o pacote para o laravel ^10.0 @GustavoSantarosa

# EN

- Introduced support for the vinkla/hashids:^11.0 package to adapt the package for Laravel ^10.0 @GustavoSantarosa

## V2.3.1 (13/03/2025)

# PT-BR

- Criado o BaseControllerExample e recriado o BaseController @GustavoSantarosa

# EN

- Created the BaseControllerExample and recreated the BaseController @GustavoSantarosa

## V2.3.0 (12/03/2025)

# PT-BR

- Removido a lib temporaria quantumtecnology/hashids que foi criado para suportar o laravel 12 e adicionado o vinkla/hashids agora com suporte para laravel 12 @GustavoSantarosa
- Acrecentado uma validação dinamica se baseando nos dados informados no config @GustavoSantarosa

# EN

- Removed the temporary library quantumtecnology/hashids that was created to support Laravel 12 and added vinkla/hashids now with support for Laravel 12 @GustavoSantarosa
- Added dynamic validation based on the data provided in the config @GustavoSantarosa

## V2.2.1 (10/03/2025)

# PT-BR

- Fixado um erro ao sobreescrever o allowedInclude @GustavoSantarosa
- Atualizado o composer com as novas atualizações usadas no pacote @GustavoSantarosa

# EN

- Fixed an error when overwriting allowedInclude @GustavoSantarosa
- Updated the composer with the latest updates used in the package @GustavoSantarosa

## V2.2.0 (04/03/2025)

# PT-BR

- Introduzido novo fkchangetrait para que seja possivel bindar ids etc que nao sigam padroes definidos, configuravel no config('hashids.attributes')
- Removido a obrigatoriedade de utilizar o baseController e passado para traits, para poder ser utilizado individualmente, isso tambem diminui o numero de metodos reservados.
- Alterado o local do BaseController e colocado dentro de controllers
- Criado um ControllerInterface default para o pacote de rotas crud
- Criado o DefaultResource e DefaultService, para que nao seja mais obrigatorio criar as variaveis, isso deixa o codigo mais clean.
- Diminuido um pouco o acoplamento de alguns pacotes de terceiros

# EN

- Introduced new fkchangetrait so that it is possible to quand ids etc that do not follow defined patterns, configurable in config ('hashids.attributes')
- Removed the obligation to use basecontroller and passed to traits, to be used individually, this also decreases the number of reserved methods.
- Changed the baseController location and placed within controller
- Created a default control for the crud route package
- Created Defaultresource and Defaultservice, so that it is no longer required to create the variables, this makes the cleaner code.
- The coupling of some packets of third parties a little

## V2.1.10 (04/03/2025)

# PT-BR

- Corrigido os problemas com phpcsfixer na pipeline do git

# EN

- Fixed problems with phpcsfixer in Git pipeline
