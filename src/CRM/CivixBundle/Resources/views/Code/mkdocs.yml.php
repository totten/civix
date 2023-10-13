# Use mkdocs to generate a manual for this extension. For more information about
# mkdocs, see https://docs.civicrm.org/dev/en/latest/documentation/#mkdocs
site_name: <?php echo "$fullName\n"; ?>
repo_url: https://lab.civicrm.org/extensions/FIXME
site_description: 'A guide for the <?php echo "$fullName\n"; ?> extension.'
site_author: FIXME
theme:
  name: material

nav:
- Home: index.md

markdown_extensions:
  - attr_list
  - admonition
  - def_list
  - pymdownx.highlight
      guess_lang: false
  - toc:
      permalink: true
  - pymdownx.superfences
  - pymdownx.inlinehilite
  - pymdownx.tilde
  - pymdownx.betterem
  - pymdownx.mark

plugins:
  - search:
      lang: en
