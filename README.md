# SisGêneros
> Sistema de Gerenciamento de Gêneros Licitados e Não Licitados do **CeIMBe**

#### Dependências
- SQlite 3.11+
- wkhtmltopdf 0.12.3
- Apache Server 2.4+
  - mod_rewrite
  - libapache2-mod-php
- PHP 7.0+
  - PHP-PDO
  - PHP-Common
 
> além das dependências de software, temos as dependências de módulos que devem estar disponíveis nestes softwares.

#### Instalação
A instalação do sistema pode ser feita seguindo os seguintes passos:
> ATENÇÃO: Os passos para instalação descritos nesta documentação, assumem que a aplicação rodará em uma máquina Linux (preferencialmente Ubuntu 16.04 LTS) e que todas a dependências já foram instaladas e configuradas.

1. Clonar ou Baixar a ultima versão deste projeto diretamente na `Home` de usuário
```bash
$ cd ~/
```
Caso você tenha optado por baixar o arquivo zipado da ultima versão, descompacte o mesmo e entre no diretório criado por este processo.
```bash
$ cd ~/sisgeneros-mb-master
```
2. Após entrar no diretório do projeto, é necessário fornecer permissão de escrita ao diretório `~/sisgeneros-mb-master/App/Database/`.
```bash
$ sudo chgrp www-data -R ~/sisgeneros-mb-master/App/Database/
$ sudo chmod 775 -R ~/sisgeneros-mb-master/App/Database/
```
3. Após dar permissão de escrita no diretório informado no passo anterior, será necessário alterar os valores que correspondem a sua OM no arquivo `~/sisgeneros-mb-master/App/Config/Configurations.php`:
```php
// código omitido
    const DOMAIN = 'www.ceimbe.mb';
    const ADMIN_CONTACT = 'E-mail: bruno.monteirodg@gmail.com';
// código omitido
```
A constante `DOMAIN` deve ser alterada para o domínio da sua OM. Quanto a constante `ADMIN_CONTACT`, deve ser alterada para o e-mail do Administrador do sistema.

Também será necessário alterar as informações contidas no arquivo `~/sisgeneros-mb-master/htr.json`. Abra o arquivo `htr.json` e altere os valores de acordo com sua necessidade.

4. Com as constantes alteradas e o arquivo salvo (e fechado), agora será necessário executar o arquivo `setup.php`:
```bash
$ php setup.php
```
Se tudo ocorrer com sucesso, a seguinte saída deve ser observada no terminal:
```bash
$ php setup.php
> Chave SALT alterada com sucesso
> Path do Core alterado com sucesso
> Path do autoload alterado com sucesso
> Arquivo de Sqlite criado com sucesso.
> Dados padrão inseridos com sucesso.
> Usuário Administrador alterado com sucesso
>> Configurações finalizadas.
```
5. Após realizar a execução do `setup.php`, crie um diretório com o nome `app` na raiz do seu site (**DocumentRoot** do Apache) e copie o diretório `public` da raiz do projeto (`~/sisgeneros-mb-master/public`) para dentro do diretório `app`. Após realizar a cópia para dentro de `app`, renomeie `public` dentro de `app` para `sisgeneros`.

Após realizar todas as configurações descritas acima, já é possível acessar o sistema no browser. O endereço deve parecer com [www.suaom.mb/app/sisgeneros](http://www.suaom.mb/app/sisgeneros).
Por padrão o sistema tem uma conta com nível `ADMINISTRADOR` que pode ser acessada para dar início as edições dentro do sistema. Para acessar o sistema basta usar as seguintes credenciais:
```
usuário: administrador
senha: administrador
```
No primeiro acesso de todo usuário é necessário fornecer uma outra senha.
Caso haja erro 404, significa que seu apache não foi configurado corretamente. Verifique se o módulo `mod_rewrite` está habilitado.

#### Servidor Apache
Aqui você pode se basear em como configurar seu servidor HTTP, porém as configurações podem mudar entre versões e distribuições Linux. Aqui estamos tomando como base uma distribuição `Ubuntu 16.04 LTS`.
Primeiro deve ser habilitar o módulo `mod_rewrite`:
```bash
$ sudo a2enmod rewrite
```
Após a execução, será necessário editar o arquivo `/etc/apache2/sites-enabled/000-default.conf`.
```bash
$ sudo nano /etc/apache2/sites-enabled/000-default.conf
```
Procure pelas configuração que apontam para o seu **DocumentRoot**. Tomando como base **DocumentRoot** como `/var/www/html`:
```
<Directory /var/www/html/>
# configs omitidas
Options Indexes FollowSymLinks MultiViews
AllowOverride All
Order allow,deny
allow from all
# configs omitidas
</Directory>
```
Salve o arquivo e reinicie o serviço do **Apache Server**
```bash
$ sudo service apache2 restart
```
Agora seu servidor já está configurado e a aplicação já pode ser acessada.

#### Geração de PDF
A aplicação faz uso de um binário que auxilia na criação de arquivos PDF pela biblioteca `knp-snappy` (já presente no sistema). Este binário é o [wkhtmltopdf](https://wkhtmltopdf.org/) e encontra-se no path `~/sisgeneros-mb-master/vendor/h4cc/wkhtmltopdf-i386/bin/wkhtmltopdf-i386`. Será necessário criar um link simbólico dentro de `/usr/bin/`.
```bash
$ sudo ln -s ~/sisgeneros-mb-master/vendor/h4cc/wkhtmltopdf-i386/bin/wkhtmltopdf-i386 /usr/bin/wkhtmltopdf
```

#### Créditos
Esta aplicação foi desenvolvida por [Edson B S Monteiro](mailto:bruno.monteirodg@gmail.com) com a participação de [Paulo Henrique Coelho Gaia](mailto:phenriquegaia@gmail.com).

## LAUS DEO .'.