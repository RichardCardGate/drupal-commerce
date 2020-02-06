![CardGate](https://cdn.curopayments.net/thumb/200/logos/cardgate.png)

# CardGate Modul für Drupal Commerce

[![Build Status](https://travis-ci.org/cardgate/drupal-commerce.svg?branch=master)](https://travis-ci.org/cardgate/drupal-commerce)

## Support

Dieses Modul is geeignet für Drupal version **7.x** und verwendet Commerce Version **1.x**

## Vorbereitung

Um dieses Modul zu verwenden sind Zugangsdate zur CardGate RESTful API notwendig.
Gehen zu [My CardGate](https://my.cardgate.com/) und fragen Sie Ihre Zugangsdaten an, oder kontaktieren Sie Ihren Accountmanager.

## Installation

1. Downloaden und entpacken Sie den aktuellsten [Source Code](https://github.com/cardgate/drupal-commerce/releases/) auf Ihrem Desktop.

2. Uploaden Sie den **Inhalt** der Zip-Datei in den **Root-Ordner** auf Ihrem Webshop.

## Configuration

1. Gehen Sie zum **Admin**-Bereich Ihres Webshops und selektieren Sie **Modules**.
   Scrollen Sie bis zum **Commerce (CardGate)** Bereich.

2. Wählen Sie alle **Zahlungsmittel** aus, die Sie aktivieren möchten.
   Scrollen Sie nach unten und klicken  auf **Save configuration**.

3. Gehen Sie zum **Admin**-Bereich Ihres Webshops und selectieren Sie **Store,  Configuration, Payment Methods**.

4. Klicken Sie bei **CardGate Generic** auf den **Edit** link.
   Klicken Sie bei **Actions** auf den **Edit** link.

5. Füllen Sie **Site ID** und **Hash key** in, diese können Sie unter Websites bei [My CardGate](https://my.cardgate.com/) finden.

6. Füllen Sie die **Merchant ID** und den **API key** ein, den Sie von GardGate empfangen haben.

7. Bevor Sie mit dem Testen beginnen, wählen sie den **Test-Mode** aus und klicken Sie auf **speichern**.

8. Gehen Sie zurück in den **Admin**-Bereich von Ihrem Webshop und wählen Sie **Store, Configuration, Payment methods** aus.

9. Wählen Sie in der Übersicht die **nicht eingeschalteten Zahlungsmittel**, die Zahlungsmittel die Sie aktivieren möchten aus und klicken Sie auf den **Edit** link.
   Klicken Sie bie Actions auf den Edit link.
   Selektieren Sie richtige **Währung** aus und **speichern** Sie die Einstellungen.

10. Gehen Sie zum **Settings** and Klicken Sie **active** an, sodass die Zahlungsmittel auf Ihrer Bezahlseite sichtbar  werden.

11. Wiederholen Sie die **Schritte 9 und 10** für jedes Zahlungsmittel, dass Sie aktivieren möchten.

12. Sorgen Sie dafür, dass Sie **nach dem Testen** bei **CardGate Generic modul** vom **Testmodus** in den **Livemodus** umschalten und **speichern** Sie die Einstellung.

## Anforderungen

Keine weiteren Anforderungen.
