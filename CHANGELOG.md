# CHANGE LOG

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
