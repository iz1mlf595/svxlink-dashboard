# 📻 Dashboard SvxLink per Bookworm ver. 1.9.xx

Dashboard avanzata sviluppata per la gestione e il monitoraggio dei nodi e dei ripetitori SvxLink su Debian Bookworm.
<br>
<br>
Passaggi per installazione della Dashboard :
<br>
<br>
1. Clona la repository dalla directory /var/www/html (o dalla cartella desiderata)
<br>
cd /var/www/html
<br>
sudo git clone https://github.com/iz1mlf595/svxlink-dashbaord.git
<br>
2. Entra nella cartella appena creata
<br>
cd /var/www/html
<br>
3. Assegna i permessi corretti ad Apache
<br>
sudo chown -R www-data:www-data /var/www/html/
<br>
sudo find /var/www/html/ -type d -exec chmod 755 {} \;
<br>
sudo find /var/www/html/ -type f -exec chmod 644 {} \; 


## 🖼️ Anteprime delle Schermate

### 1. Vista Principale della Dashboard in DarkMode
<img width="1607" height="887" alt="Image" src="https://github.com/user-attachments/assets/4412956a-2073-4e1a-8389-7dd0daeae9ed" />

### 2. Vista Principale della Dashboard in versione Light
<img width="1656" height="903" alt="Image" src="https://github.com/user-attachments/assets/acc6b788-657e-4b9e-a8f9-987db26afd1c" />

### 3. Vista del menu di controllo della Gestione di Sistema
<img width="1708" height="772" alt="Image" src="https://github.com/user-attachments/assets/c3a764b4-5e20-42e5-8725-0eb7a4ffd2bf" />
