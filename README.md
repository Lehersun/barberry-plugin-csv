barberry-plugin-csv
===================

Barberry plugin for CSV-to-CSV conversions.

Supported underscore-delimited subcommands:

- `utf8`
- `comma`
- `utf8_comma`
- `comma_utf8`

Behavior:

- `utf8` decodes UTF-8, UTF-8 with BOM, and unambiguous legacy encodings, then emits UTF-8.
- `comma` detects `,`, `;`, tab, or `|` as the source delimiter and rewrites output as comma-separated CSV.
- Unknown, duplicated, or malformed subcommands fail through Barberry's existing command/conversion flow.
- Only CSV-like text input is supported; spreadsheet formats are intentionally out of scope.
