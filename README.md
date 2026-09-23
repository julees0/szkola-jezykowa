Szkoła Językowa – System Rezerwacji Zajęć
Aplikacja internetowa do obsługi rezerwacji zajęć w szkole językowej. Pozwala klientom na przeglądanie oferty kursów, wybór lektora i rezerwację terminów lekcji. Lektorzy oraz administrator posiadają dedykowane panele do zarządzania grafikiem, zmianą statusów zajęć oraz edycją oferty.

Autorzy
-Julian Dmowski
-Adrian Cibor

Technologie:
Backend: PHP (czysty kod, połączenie z bazą przez PDO)
Frontend: HTML5, CSS3, JavaScript (AJAX)
Baza danych: MySQL (InnoDB, kodowanie utf8mb4)
Wersjonowanie: Git / GitHub (model pracy: branch → PR → main)
Struktura bazy danych
Plik database/database.sql zawiera relacyjną strukturę bazy danych dostosowaną do wymagań projektu:
users – konta użytkowników z podziałem na role (client, employee, admin).
employees – dodatkowe profile i biogramy lektorów.
service_categories oraz services – katalog języków i konkretnych zajęć (z określeniem czasu trwania i ceny).
employee_services – przypisanie lektorów do prowadzonych przez nich kursów (relacja N:M).
reservations – rezerwacje zajęć składane przez klientów.
employee_availability – cotygodniowe grafiki dostępności lektorów.
Baza danych posiada nałożone klucze obce (FOREIGN KEY) oraz ograniczenia unikalności (UNIQUE), m.in. dla adresów e-mail oraz nazw kategorii językowych, co zapobiega duplikowaniu danych.

Diagram encji znajduje się w pliku ERD.png

Jak uruchomić lokalnie
Zaimportuj plik database/database.sql do MySQL (np. przez phpMyAdmin w XAMPP).
Skopiuj pliki projektu do katalogu serwera lokalnego (np. htdocs).
Ustaw prawidłowe dane połączenia w pliku includes/db.php.
Otwórz projekt w przeglądarce pod adresem: http://localhost/nazwa_folderu/index.php

Konta testowe
Wszystkie konta posiadają domyślne hasło: zaq1@WSX
Administrator: admin@szkola.pl
Pracownik (Lektor): ewa.lektor@szkola.pl
Klient: jan.klient@gmail.com

Status projektu
Etap 1: Konfiguracja repozytorium Git, struktura folderów, projekt i plik bazy danych, dokumentacja README.md.
Kolejne etapy: Obsługa rejestracji/logowania w PHP, panele użytkowników, integracja kalendarza rezerwacji i obsługa zmian statusów.
