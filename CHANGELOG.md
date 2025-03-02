CHANGE LOG
==========


## V2.x.x (02/03/2025)

* Removido a obrigatoriedade de utilizar o baseController e passado para traits, para poder ser utilizado individualmente, isso tambem diminui o numero de metodos reservados.
* Alterado o local do BaseController e colocado dentro de controllers
* Criado um ControllerInterface default para o pacote de rotas crud
* Criado o DefaultResource e DefaultService, para que nao seja mais obrigatorio criar as variaveis, isso deixa o codigo mais clean.
* Diminuido um pouco o acoplamento de alguns pacotes de terceiros
