# CHANGE LOG

## V2.7.0 (20/05/2025)

# PT-BR

- Corrigindo a validação do decoder para headers, params e body. @bhcosta90
- Atualizado as regras do phpcsixer para se adequar aos demais pacotes. @GustavoSantarosa
- O pacote, perdeu suporte para o php 8.2, agora somente para php 8.3 ou superior. @GustavoSantarosa

# EN

- Correcting Decoder Validation for Headers, Params and Body @bhcosta90
- Updated phpcsfixer rules to match other packages. @GustavoSantarosa
- Dropped support for PHP 8.2, now requires PHP 8.3 or higher. @GustavoSantarosa

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
