Szkoła Językowa – System Rezerwacji Zajęć<br>
Aplikacja internetowa do obsługi rezerwacji zajęć w szkole językowej. Pozwala klientom na przeglądanie oferty kursów, wybór lektora i rezerwację terminów lekcji. Lektorzy oraz administrator posiadają dedykowane panele do zarządzania grafikiem, zmianą statusów zajęć oraz edycją oferty.

Autorzy<br>
-Julian Dmowski <br>
-Adrian Cibor <br> 

Technologie:<br>
Backend: PHP (czysty kod, połączenie z bazą przez PDO)<br>
Frontend: HTML5, CSS3, JavaScript (AJAX)<br>
Baza danych: MySQL (InnoDB, kodowanie utf8mb4)<br>
Wersjonowanie: Git / GitHub (model pracy: branch → PR → main)<br>

Struktura bazy danych<br>
Plik database/database.sql zawiera relacyjną strukturę bazy danych dostosowaną do wymagań projektu:<br>
users – konta użytkowników z podziałem na role (client, employee, admin)<br>
employees – dodatkowe profile i biogramy lektorów<br>
service_categories oraz services – katalog języków i konkretnych zajęć (z określeniem czasu trwania i ceny)<br>
employee_services – przypisanie lektorów do prowadzonych przez nich kursów (relacja N:M)<br>
reservations – rezerwacje zajęć składane przez klientów<br>
employee_availability – cotygodniowe grafiki dostępności lektorów<br>
Baza danych posiada nałożone klucze obce (FOREIGN KEY) oraz ograniczenia unikalności (UNIQUE), m.in. dla adresów e-mail oraz nazw kategorii językowych, co zapobiega duplikowaniu danych<br>

Diagram encji znajduje się w pliku ERD.png

Jak uruchomić lokalnie<br>
Zaimportuj plik database/database.sql do MySQL (np. przez phpMyAdmin w XAMPP)<br>
Skopiuj pliki projektu do katalogu serwera lokalnego (np. htdocs)<br>
Ustaw prawidłowe dane połączenia w pliku includes/db.php<br>
Otwórz projekt w przeglądarce pod adresem: http://localhost/nazwa_folderu/index.php<br>

Status projektu<br>
Etap 1: Konfiguracja repozytorium Git, struktura folderów, projekt i plik bazy danych, dokumentacja README.md<br>
Kolejne etapy: Obsługa rejestracji/logowania w PHP, panele użytkowników, integracja kalendarza rezerwacji i obsługa zmian statusów<br>
