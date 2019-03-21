### v2.x para v3.x

#### Dependências
Para realizar a migração, siga os passos descritos na seção [Istalação](https://github.com/br-monteiro/sisgeneros-mb#instala%C3%A7%C3%A3o) (**EXCETO O ITEM 4**) na documentação principal.
Além disso, antes de prosseguir, save o valor da constante `STR_SALT` presente no arquivo `App/Config/Configurations.php` do seu sistema na versão **2.x**.
Após guardar esse valor num lugar seguro, basta seguir os passos descritos abaixo:

#### 1 - Execução do setup
Para migrar os dados do banco `Sqlite` (usado na versão **v2.x**) para `MySQL` foi adicionado um arquivo chamado `migrates.php`, localizado na raiz do projeto. Porém, antes de executar este arquivo é necessário reconfigurar o sistema com o token antigo (valor de `STR_SALT` salvo anteriormente). Para setar este valor de forma segura, execute o seguinte comando:

```bash
$ php setup.php com-chave=<valor-da-chave>
```

Por exemplo, considerando que o valor da chave é `H3388hhh4hh33933u3`, o comando seria executado da seguinte forma:

```bash
$ php setup.php com-chave=H3388hhh4hh33933u3
```

Este comando apresentará uma saída igual a esta:

```bash
$ php setup.php com-chave=H3388hhh4hh33933u3
> Permissões de acesso no diretório de backup de tokens setadas com sucesso
> Token salvo com sucesso
> Chave SALT alterada com sucesso
> Path do Core alterado com sucesso
> Path do autoload alterado com sucesso
> Banco de Dados criado com sucesso
> Dados padrão inseridos com sucesso
> Usuário Administrador alterado com sucesso
> Permissões de acesso no diretório de upload setadas com sucesso
>> Configurações finalizadas
```

A partir deste ponto os sistema já deve estar configurado para a nova versão.

#### 2 - Executando a migração
Conforme descrito anteriormente, há um script utilitário para facilitar a migração de dados.
Antes de iniciar a execução, é necessário ter configurado as credenciais de acesso ao banco de dados **MySQL**
conforme descrito no item 3 da documentação principal ([Istalação](https://github.com/br-monteiro/sisgeneros-mb#instala%C3%A7%C3%A3o)). 
Assumindo que este passo já foi configurado, copie o arquivo `sisgeneros.db` presente no projeto com a versão **v2.x**. O arquivo pode ser encontrado em `App/Database/sisgeneros.db`. Copie este arquivo do projeto com a versão antiga e cole no diretório `App/Database/` do projeto com a versão **v3.x**.
Seguindo com a migração, execute o comando abaixo:

```bash
php migrates.php --executar
```

Se tudo ocorrer de forma correta, você verá as tabelas que o sistema está migrando.

Ao término da execução, os usuários já poderão acessar o sistema com as suas credencias cadastradas.
Todos os dados das contidos nas tabelas dos sistema antigo foram migradas para o novo SGDB.

#### Backup de token
Da versão `v3.x` em diante, como forma de evitar a perda das chaves geradas automaticamente pelo sistema, o mesmo faz o backup do valor de `STR_SALT` em arquivos de texto no diretório `App/Config/keys/`.
Por padrão os arquivos são gerados com a data e a hora do momento da execução do `setup.php`. Por exemplo: **2019-03-21_09-03-32.txt**
