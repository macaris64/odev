-- E-POSTA UYGULAMASI --

İstenen :
Klasörün içindeki a.txt dosyasında yazan adreslerin doğruluğunu kontrol etmek.
Önerilen site : https://email-checker.net/

Uygulamada CURL komutunu kullandım. Index.php dosyasında CURL ile , formdan post edilen email ve _csrf değerlerini gönderdim. 
Id değeri sayfa her yenilendiğinde değişiyordu. Bu yüzden sayfa her açıldığında _csrf buldurma işlemi yaptım. 
Email ve _csrf'ler doğru gönderilmesine rağmen herhangi bir çıktı alamadım.
Curl komutundan sonra , Websocket , webservice konularını araştırdım fakat kayda değer bir şey bulamadım. 

İndex1.php dosyasında CURL ile emailChecker ifadesini el ile gönderdim ve gönderdikten sonra başarılı bir şekilde çıktı elde ettim .Fakat bulduğum çıktı, sadece verileri gönderdikten sonra açılan sayfayı refresh etmemle görünüyor. Tam verimli çalışmıyor.

Mehmet Acar

// Edit 

_csrf token'in saldırılardan korunmak için kullanılan bir değer olduğunu öğrendim. csrf : sistemin açığından faydalanarak sisteme sanki o kişiymiş gibi erişerek işlem yapmayı sağlar. csrf token'i araştırdım.

// Edit

csrf'ye yönelik pek kayda değer bir şey bulamadım. Github'dan araştırırken bir kütüphane buldum. Onun kaynak kodlarında bizim aradığımız şeye benzer bir proje olduğunu gördüm. O projeyi sistemime ekledim. 

Araştırdığım Konular
____________________

1- cURL
2- WebService
3- WebSockets
4- cURL / Telnet connection
5- _csrf Token
