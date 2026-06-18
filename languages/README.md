# Translations — handoff to the main-plugin catalog

The add-on renders the redesigned checkout with the **`yourpropfirm` textdomain**
(`esc_html_e( '…', 'yourpropfirm' )`), the same domain the main plugin translates
via its custom locale system (`?lang=` / `ypf_lang` cookie → `locale` filter →
`reload_textdomain`).

**Nothing here is broken in the add-on.** Strings that already exist in the main
plugin's catalog (e.g. `Total`, `Currency`, `Trading Platform`) translate
correctly. The redesign just introduced **new UI strings that have no entry in
the catalog yet**, so gettext returns the English original for those.

Confirmed locally (German): with the add-on active, `<html lang="de-DE">` is set
and catalog strings render in German (`Gesamt`, `Währung`, `Handelsplattform`);
only the new strings stay English.

## Files

- **`yourpropfirm-ui-addon-missing.pot`** — the **42 strings missing** from the
  catalog (the actionable handoff). Each entry keeps its `#:` source reference.
- `yourpropfirm-ui-addon.pot` — the full add-on string set (132), for reference.

## To fix (main-plugin side — no add-on code change needed)

For each of the 13 language `.po` files in `yourpropfirm-plugin/languages/`:

```bash
msgmerge --update yourpropfirm-<locale>.po yourpropfirm-ui-addon-missing.pot
# translate the newly-added (untranslated) entries, then:
msgfmt yourpropfirm-<locale>.po -o yourpropfirm-<locale>.mo
```

Because the add-on already uses the `yourpropfirm` textdomain, the moment these
strings land in the `.mo` files they translate automatically on the checkout —
**no change to the add-on is required.**
