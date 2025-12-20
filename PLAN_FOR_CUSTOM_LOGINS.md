## Custom Logins
Wir brauchen custom designte logins fuer verschiedene domains, wobei es immer unser login ist, der design infos basierend auf der domain von der DB bekommt.

## Wichtige Aspekte
- Aktiviert & Domain eingetellt wird in Project Info, sowie das design selbst, fuers erste nur primary color und logo
- Danach muss das in DB gespeichert werden und die Cloudflare API genutzt um einen A record auf 92.5.112.145 zu setzen (Cloudflare intergration haben wir schon irgendwo im backend)
- Die Login Seite selbst muss umgebaut werden um custom farben und Logo zu erlauben und axios der es von der DB entnimmt
- Wir brauchen ein script der die nginx settings am server setted, dieser script ist *nicht* mit dem main backend verbunden, er ist am selben server wie da frontend, wobei das main backend auf einem anderen ist, somit einfach "/...... script irgendwo"
- Wir muessen SSLs handelt sollte aber durch Cloudflare automatisiert gehen / oder Certbot

## Sehr wichtig!!!
- Beachte aktuelle backend Strukturen, heisst kein curl usw.