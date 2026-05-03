# Entity-field parsers

This namespace contains the parsers used by `RawDrupalContext::parseEntityFields()` to convert raw stub field values into Drupal-ready structured arrays.

Two implementations live here:

- `EntityFieldParser` - the current parser. Default at runtime.
- `LegacyEntityFieldParser` - the previous parser, kept available behind the `field_parser: legacy` extension parameter for migration. Frozen and scheduled for removal in 6.1.

Both implement `EntityFieldParserInterface` and own everything between the raw stub map and the final stub map: textual parsing, `field:column` / `:column` multicolumn-header merging, configurable / base / ignored field validation, and the unknown-field guard. The context layer just chooses an instance and delegates.

## Modern syntax

A single uniform escape mechanism (double quotes) for compound values. Cells fall into two modes detected by the value form, not by the spacing of separators:

- **Scalar mode** (no top-level `key:"...` or `key:[...]` pattern):
  - Plain text or comma-separated list of items.
  - Items containing `,` or `;` or `"` must be wrapped in `"..."`.
- **Compound mode** (top-level `key:"...` or `key:[...]` pattern present):
  - One or more `key: value` columns separated by `,`.
  - Multi-value compound: records separated by `;`.
  - Each column value MUST be a quoted string (`"..."`) or token (`[name:value]`). Bare values are a parse error.
  - Inside `"..."`: `\"`, `\\`, `\n`, `\t`, `\r` are recognised escape sequences; any other backslash sequence is an error.

Whitespace around `,`, `;` and `:` is ignored outside quoted strings and tokens. Whitespace inside `"..."` is preserved literally.

Errors detected while parsing a single cell are collected and thrown together via `MultipleParseException` so authors see every problem at once instead of fixing them one at a time.

## Validity table

| #  | Value type                               | Field type example       | Modern syntax                                                          |
|----|------------------------------------------|--------------------------|------------------------------------------------------------------------|
| 1  | scalar, single                           | string / integer / email | `Hello`                                                                |
| 2  | scalar, single, contains `:`             | text_long                | `Note: this is important`                                              |
| 3  | scalar, single, contains `,`             | text_long                | `"Hello, world"`                                                       |
| 4  | scalar, single, contains ` - `           | entity_reference title   | `Alpha - Bravo`                                                        |
| 5  | scalar, single, contains `;`             | text_long                | `"Hello; world"`                                                       |
| 6  | scalar, looks like `key:value`           | string                   | `port:8080`                                                            |
| 7  | scalar, multi-value                      | string / list            | `Tag one, Tag two`                                                     |
| 8  | scalar, multi-value, item contains `,`   | string                   | `Tag one, "Tag, two"`                                                  |
| 9  | token, single                            | datetime relative        | `[relative:-1 week]`                                                   |
| 10 | token, multi                             | datetime relative        | `[relative:-1 week], [relative:-2 weeks]`                              |
| 11 | token in scalar prose                    | text_long                | `Posted [relative:-1 week] ago`                                        |
| 12 | token at compound value position         | daterange                | `value:[relative:-1 week], end_value:[relative:+1 week]`               |
| 13 | compound named, single                   | text_with_summary        | `value:"Body", summary:"Summary", format:"basic_html"`                 |
| 14 | compound named, single                   | address                  | `country:"BE", locality:"Brussel", postal_code:"1000"`                 |
| 15 | compound named, single                   | daterange                | `value:"2026-01-01", end_value:"2026-12-31"`                           |
| 16 | compound named, single                   | file / image             | `target_id:"foo.jpg", alt:"A", title:"B"`                              |
| 17 | compound named, value contains `,`       | address                  | `country:"BE", locality:"Brussel, X", postal_code:"1000"`              |
| 18 | compound named, value contains `:`       | address                  | `street:"Main: 1", country:"BE"`                                       |
| 19 | compound named, value contains `;`       | address                  | `street:"A;B", country:"BE"`                                           |
| 20 | compound named, value contains escaped `"` | text                   | `note:"He said \"hi\""`                                                |
| 21 | compound named, multi                    | address                  | `country:"BE", locality:"Brussel"; country:"FR", locality:"Paris"`     |
| 22 | compound named, multi                    | link                     | `title:"Link 1", uri:"http://a"; title:"Link 2", uri:"http://b"`       |
| 23 | compound positional, single              | link                     | not supported - use named keys                                         |
| 24 | compound positional, multi               | link                     | not supported - use named keys                                         |
| 25 | nested compound                          | paragraphs               | not supported inline - use multicolumn header rows                     |
| 26 | external multicolumn header              | any                      | `field:col` row + `:col` rows merged into one canonical field          |
| 27 | whitespace tolerance                     | any                      | compact and spaced forms both accepted                                 |

## Configuration

Selected via the `field_parser` extension parameter in `behat.yml`:

```yaml
default:
  extensions:
    Drupal\DrupalExtension:
      field_parser: default   # one of: default | legacy
```

`legacy` is opt-in and emits a deprecation notice once per process. It is removed in 6.1.

## Errors

Parse errors thrown by the modern parser carry:

- `errorCode` - machine-readable identifier (`unclosed_quote`, `unknown_escape`, `unquoted_compound_value`, `empty_record`, `empty_column`, `unclosed_token`, `unquoted_semicolon`, `unexpected_quote`, `unexpected_character`, `expected_quoted_string`, `expected_token`, `trailing_characters`, `invalid_column`).
- `offset` - zero-based character offset within the cell.
- `cell` - the cell text being parsed.
- `description` and optional `hint` - human-readable explanation.

`getMessage()` returns a multi-line string with the cell, a caret line at the offset, and the description, suitable for surfacing directly in a Behat run. `MultipleParseException` collects multiple per-cell errors so authors can fix them all in one edit.

## Upgrading

See [`UPGRADING.md`](../../../../../UPGRADING.md) for the side-by-side syntax mapping, positional-to-named conversion table, escape sequences, and parse-error format.
