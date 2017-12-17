# LdapAuth
Creazione e test autenticazione con server Ldap locale

L'installazione è stata effettuata su Windows con [OpenLdap](https://www.openldap.org/).
Passi dell'installazione:

![Image 1](https://github.com/davideDI/LdapAuth/blob/master/images/Immagine1.png)

![Image 2](https://github.com/davideDI/LdapAuth/blob/master/images/Immagine2.png)

![Image 3](https://github.com/davideDI/LdapAuth/blob/master/images/Immagine3.png)

![Image 4](https://github.com/davideDI/LdapAuth/blob/master/images/Immagine4.png)

![Image 5](https://github.com/davideDI/LdapAuth/blob/master/images/Immagine5.png)

![Image 5](https://github.com/davideDI/LdapAuth/blob/master/images/Immagine5.png)

![Image 6](https://github.com/davideDI/LdapAuth/blob/master/images/Immagine6.png)

![Image 7](https://github.com/davideDI/LdapAuth/blob/master/images/Immagine7.png)

![Image 8](https://github.com/davideDI/LdapAuth/blob/master/images/Immagine8.png)

![Image 9](https://github.com/davideDI/LdapAuth/blob/master/images/Immagine9.png)

Nella cartella OpenLdap appena creata modificare il file slapd.conf per importare eventuali nuovi schema o impostare la password dell'admin.

Dato che il database selezionato è di tipo **ldif** bisogna effettuare il caricamento delle **entry**.

*ldapmodify.exe -a -x -h localhost -p 389 -D "cn=admin,dc=test,dc=univaq,dc=it" -f C:\OpenLDAP\ldifdata\test.ldif -w secret*

dove nel file _test.ldif_ saranno indicate le entry.

**N.B. una volta effettuata l'installazione di OpenLdap riavviare il pc, per rendere effettiva l'installazione.**

**N.B. per Ubuntu consultare la seguente guida [Ldap Ubuntu](https://www.digitalocean.com/community/tutorials/how-to-install-and-configure-openldap-and-phpldapadmin-on-ubuntu-16-04).**

**N.B. per Windows 10, per poter effettuare il collegamento al server Ldap locale eseguire il comando run.cmd nella cartella .\OpenLdap\run\**
