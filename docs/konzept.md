# Gorres Blocks – Konzept und Aufbau

Version: 1.2 · Stand: 06.10.2026 · Plugin-Version: 1.2.0

## 1. Zweck

Sammel-Plugin für schlanke Blöcke im Block-Editor. Jeder Block lässt sich
einzeln ein- und ausschalten. Neue Blöcke kommen ausschließlich mit einer neuen
Plugin-Version; Code aus externen Quellen nachzuladen ist im
WordPress-Verzeichnis verboten (Richtlinie 8).

Das Plugin ist für das WordPress-Verzeichnis gedacht. Oberfläche und Texte im
Plugin sind englisch, diese Projektdoku ist deutsch.

## 2. Eckdaten

| Feld | Wert |
| --- | --- |
| Name | Gorres Blocks |
| Slug / Text Domain | `gorres-blocks` |
| Prefix | `jgor_gblk_`, `JGOR_GBLK_`, Handles `jgor-gblk-` |
| Block-Namensraum | `gorres-blocks/<block>` |
| Requires at least | 6.8 (wegen `wp_register_block_types_from_metadata_collection()`) |
| Tested up to | 7.1 (nur in `readme.txt`) |
| Requires PHP | 8.1 |
| Plugin URI | `https://github.com/jgorres/gorres-blocks-plugin` |
| Lokale Site | `https://ggb.local`, Plugin per Symlink auf `plugin/` |

Der ursprünglich gewünschte Name „GGB - Gorres Gutenberg Blocks" scheidet aus:
„gutenberg" steht auf der Trademark-Liste der Einreichungsprüfung
(Richtlinie 17) und ist im Slug an jeder Stelle gesperrt.

## 3. Verzeichnisse

Aufbau nach `~/dev/jgorres-im-WP-Repository/README.md`.

```
gorres-blocks/
├── plugin/                 ausgeliefertes Plugin
│   ├── gorres-blocks.php
│   ├── readme.txt
│   ├── uninstall.php
│   ├── package.json        für die mitgelieferten Quellen
│   ├── includes/
│   ├── src/<block>/        Block-Quellen, werden mit ausgeliefert
│   ├── build/              Build-Ergebnis inkl. blocks-manifest.php (nicht im Git)
│   └── languages/          nur gorres-blocks.pot
├── docs/  assets/  glotpress/
├── package.json            Build aus dem Repo-Root (wp-scripts --blocks-manifest)
├── composer.json           PHPCS/WPCS, PHPStan Level 6
└── build.sh                Versionsprüfung, Build, dist/gorres-blocks-<version>.zip
```

## 4. Registrierung der Blöcke (`includes/blocks.php`)

- Jeder Block liegt in `plugin/src/<block>/` mit eigenem `block.json`.
- `wp-scripts build --blocks-manifest` erzeugt `build/blocks-manifest.php`
  (Schlüssel = Ordnername).
- `wp_register_block_metadata_collection()` meldet das Manifest an, danach
  `register_block_type()` nur für die eingeschalteten Blöcke.
  `wp_register_block_types_from_metadata_collection()` scheidet aus, weil es
  immer alle Blöcke registriert.
- Manifest-Einträge mit ungültigem Ordnernamen oder ohne `name` werden
  übersprungen.
- `build.sh` findet die Blöcke selbst (jeder Ordner in `src/` mit
  `block.json`) und prüft je Block die Version im `block.json`.

## 5. Ein- und Ausschalten (`includes/admin.php`)

- Einstellungsseite „Einstellungen → Gorres Blocks" (`manage_options`) mit
  einer Checkbox je Block, Titel und Beschreibung aus dem Manifest (übersetzt
  mit Kontext `block title`/`block description` wie im Core), dazu Link
  „Settings" in der Plugin-Liste.
- Gespeichert werden die **ausgeschalteten** Blöcke in der Option
  `jgor_gblk_disabled_blocks`. Ein neuer Block aus einem Update ist damit
  sofort eingeschaltet. Ohne Option sind alle Blöcke an.
- Settings API: Nonce über `settings_fields()`, Rechte über `options.php`,
  zusätzlich Prüfung im Render-Callback. Die Sanitize-Funktion nimmt das
  Formular (`submitted` + `enabled[]`) und eine einfache Namensliste
  (`update_option()`) an und lässt nur vorhandene Blöcke durch.
- Ausgeschaltete Blöcke werden nicht registriert: kein CSS, kein JS, kein
  Eintrag im Inserter. Bereits verwendete Blöcke zeigt der Editor dann als
  „nicht unterstützt", das gespeicherte Markup bleibt im Frontend erhalten.
- `uninstall.php` löscht die Option.

## 6. Blöcke

### 6.1 Hero Teaser (`gorres-blocks/hero-teaser`)

Ablauf beim Scrollen (vom Nutzer bestätigt):

1. Die Seite lädt: Das Hero-Bild füllt den Viewport, darüber liegt der
   Textkasten mit dem Anreißer.
2. Beim Scrollen bleibt das Bild stehen; nur der Textkasten wandert nach oben,
   bis er oben aus dem Bild verschwunden ist.
3. Danach scrollt das Bild normal mit nach oben, der Beitrag folgt direkt
   darunter.

**Aufbau** (dynamischer Block, `render.php`, gespeichert werden nur die
InnerBlocks des Kastens):

```
div.jgor-gblk-hero              Wrapper, so hoch wie die Spur
├── div.jgor-gblk-hero__media   sticky, top = Versatz + Admin-Leiste, Höhe H
│   └── img                     object-fit cover, Fokuspunkt, ::after dunkelt ab
└── div.jgor-gblk-hero__track   margin-top −H
    │                           padding-top H × Startposition, padding-bottom H
    └── div.jgor-gblk-hero__box InnerBlocks (Überschrift + Absatz als Vorlage)
```

H = `100svh − Versatz − Admin-Leiste`. Die Spur ist Startabstand + Kasten + H
hoch, der Wrapper ebenso. Das Bild klebt also genau so lange, bis die
Unterkante des Kastens die Oberkante des Bildes erreicht, für jede
Kastenhöhe und ohne JavaScript.

**Abweichung vom ursprünglichen Plan:** Die senkrechte Position ist keine
Wahl oben/Mitte/unten, sondern ein Startabstand in Prozent der
Bildschirmhöhe (0–90 %, Standard 50 %). „Mitte" und „unten" bräuchten die Höhe
des Kastens, die CSS nicht kennt; mit fester Aufteilung bliebe das Bild nach
dem Kasten leer stehen. Der Startabstand stimmt dagegen immer.

**Einstellungen:** Bild (nur Mediathek, wegen srcset), Fokuspunkt,
Alternativtext (leer = aus der Mediathek), Abdunklung 0–90 %, Kasten
waagerecht links/Mitte/rechts, Startabstand, Breite 20–100 % (mindestens 20rem
bzw. volle Breite), Versatz für fixierte Header 0–300 px. Die Admin-Leiste
rechnet das CSS selbst ein (`--wp-admin--admin-bar--height`, unter 600 px
nicht, weil sie dort mitscrollt).

**Block-Supports:** Farben (Text, Hintergrund), Rahmen, Schatten und
Innenabstand mit `__experimentalSkipSerialization` auf den Kasten, gebaut in
`functions.php` (`jgor_gblk_hero_box_attributes()`) bzw. im Editor in
`box-props.js`. Außenabstand oben/unten und Ausrichtung (Standard `full`) am
Wrapper. Ohne eigene Farben: helle Karte mit dunklem Text.

**Bild:** `wp_get_attachment_image()` ohne `loading`, mit
`fetchpriority="high"` und `sizes="max(100vw, <Seitenverhältnis×100>vh)"`.

**Dateien:** `src/hero-teaser/` mit `block.json`, `index.js`, `edit.js`,
`box-props.js`, `save.js`, `render.php`, `functions.php` (Hilfsfunktionen,
per `require_once`, weil `render.php` je Instanz eingebunden wird),
`style.scss`, `editor.scss`. Der Build kopiert PHP-Dateien mit
`--webpack-copy-php`.

## Änderungen

| Version | Datum | Änderung |
| --- | --- | --- |
| 1.2 | 06.10.2026 | Hero Teaser umgesetzt (Plugin 1.2.0); senkrechte Position als Startabstand statt oben/Mitte/unten |
| 1.1 | 06.10.2026 | Registrierung und Einstellungsseite umgesetzt (Plugin 1.1.0); Option speichert ausgeschaltete Blöcke (`jgor_gblk_disabled_blocks`) |
| 1.0 | 06.10.2026 | Erste Fassung: Gerüst nach Ordnerschema, Namenswahl, Plan für Registrierung, Ein-/Ausschalten und Hero-Block |
