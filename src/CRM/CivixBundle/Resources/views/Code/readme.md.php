# <?php echo $fullName; ?>


![Screenshot](/images/screenshot.png)

(*FIXME: In one or two paragraphs, describe what the extension does and why one would download it. *)

The extension is licensed under [<?php echo $license ?>](LICENSE.txt).

## Requirements

* PHP v7.4+
* CiviCRM (*FIXME: Version number*)

## Installation (Web UI)

Learn more about installing CiviCRM extensions in the [CiviCRM Sysadmin Guide](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/).

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl <?php echo $fullName; ?>@https://github.com/FIXME/<?php echo $fullName; ?>/archive/master.zip
```
or
```bash
cd <extension-dir>
cv dl <?php echo $fullName; ?>@https://lab.civicrm.org/extensions/<?php echo $fullName; ?>/-/archive/main/<?php echo $fullName; ?>-main.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/FIXME/<?php echo $fullName; ?>.git
cv en <?php echo $mainFile . "\n"; ?>
```
or
```bash
git clone https://lab.civicrm.org/extensions/<?php echo $fullName; ?>.git
cv en <?php echo $mainFile . "\n"; ?>
```

## Getting Started

(* FIXME: Where would a new user navigate to get started? What changes would they see? *)

## Known Issues

(* FIXME *)
