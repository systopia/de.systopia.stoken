#!/bin/sh

l10n_tools="../civi_l10n_tools"

# extract all 'regular' ts() string
${l10n_tools}/bin/create-pot-files-extensions.sh de.systopia.stoken  ./ l10n
