CHANGE LOG
==========


## V2.x.x (02/03/2025)

# PT-BR
* Removido a obrigatoriedade de utilizar o baseController e passado para traits, para poder ser utilizado individualmente, isso tambem diminui o numero de metodos reservados.
* Alterado o local do BaseController e colocado dentro de controllers
* Criado um ControllerInterface default para o pacote de rotas crud
* Criado o DefaultResource e DefaultService, para que nao seja mais obrigatorio criar as variaveis, isso deixa o codigo mais clean.
* Diminuido um pouco o acoplamento de alguns pacotes de terceiros

# EN
* Removed the obligation to use basecontroller and passed to traits, to be used individually, this also decreases the number of reserved methods.
* Changed the baseController location and placed within controller
* Created a default control for the crud route package
* Created Defaultresource and Defaultservice, so that it is no longer required to create the variables, this makes the cleaner code.
* The coupling of some packets of third parties a little
