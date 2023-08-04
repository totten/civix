#!/usr/bin/env pogo
<?php
#!depdir ../extern/releaser-deps
#!require clippy/std: ~0.4.6
#!require clippy/container: '~1.2'

###############################################################################
## Bootstrap
namespace Clippy;

use Symfony\Component\Console\Style\SymfonyStyle;

assertThat(PHP_SAPI === 'cli', "Releaser may only run via CLI");
$c = clippy()->register(plugins());

###############################################################################
## Configuration

$c['ghRepo'] = 'totten/civix';
$c['srcDir'] = fn() => realpath(dirname(pogo_script_dir()));
$c['buildDir'] = fn($srcDir) => autodir("$srcDir/build");
$c['distDir'] = fn($buildDir) => autodir("$buildDir/dist");
$c['toolName'] = fn($boxOutputPhar) => preg_replace(';\.phar$;', '', basename($boxOutputPhar));
$c['gcloudUrl'] = fn($toolName) => joinUrl('gs://civicrm', $toolName);

// Ex: "v1.2.3" ==> publishedTagName="v1.2.3", publishedPharName="mytool-1.2.3.phar"
// Ex: "1.2.3"  ==> publishedTagName="v1.2.3", publishedPharName="mytool-1.2.3.phar"
$c['publishedTagName'] = fn($input) => preg_replace(';^v?([\d\.]+);', 'v\1', $input->getArgument('new-version'));
$c['publishedPharName'] = fn($toolName, $publishedTagName) => $toolName . "-" . preg_replace(';^v;', '', $publishedTagName) . '.phar';

###############################################################################
## Services and other helpers

$c['gpg'] = function(Credentials $cred): \Crypt_GPG {
  // It's easier to sign multiple files if we use Crypt_GPG wrapper API.
  #!require pear/crypt_gpg: ~1.6.4
  $gpg = new \Crypt_GPG(['binary' => trim(`which gpg`)]);
  $gpg->addSignKey($cred->get('GPG_KEY'), $cred->get('GPG_PASSPHRASE'));
  return $gpg;
};

$c['boxJson'] = function(string $srcDir): array {
  $file = $srcDir . '/box.json';
  assertThat(file_exists($file), "File not found: $file");
  return fromJSON(file_get_contents($file));
};

// Ex: /home/me/src/mytool/bin/mytool.phar
$c['boxOutputPhar'] = function($srcDir, $boxJson) {
  assertThat(!empty($boxJson['output']), 'box.json must declare output file');
  return $srcDir . '/' . $boxJson['output'];
};

/**
 * Make a directory (if needed). Return the name.
 * @param string $path
 * @return string
 */
function autodir(string $path): string {
  if (!file_exists($path)) {
    mkdir($path);
  }
  return $path;
}

###############################################################################
## Commands
$globalOptions = '[-N|--dry-run] [-S|--step]';
$commonOptions = '[-N|--dry-run] [-S|--step] new-version';

$c['app']->command("release $commonOptions", function (string $publishedTagName, SymfonyStyle $io, Taskr $taskr) use ($c) {
  if ($vars = $io->askHidden('(Optional) Paste a batch list of secrets (KEY1=VALUE1 KEY2=VALUE2...)')) {
    assertThat(!preg_match(';[\'\\"];', $vars), "Sorry, not clever enough to handle meta-characters.");
    foreach (explode(' ', $vars) as $keyValue) {
      [$key, $value] = explode('=', $keyValue, 2);
      putenv($keyValue);
      $_ENV[$key] = $_SERVER[$key] = $value;
    }
  }

  $taskr->subcommand('tag {{0|s}}', [$publishedTagName]);
  $taskr->subcommand('build {{0|s}}', [$publishedTagName]);
  $taskr->subcommand('sign {{0|s}}', [$publishedTagName]);
  $taskr->subcommand('upload {{0|s}}', [$publishedTagName]);
  $taskr->subcommand('tips {{0|s}}', [$publishedTagName]);
  // TODO: $taskr->subcommand('clean {{0|s}}', [$publishedTagName]);
});

$c['app']->command("tag $commonOptions", function ($publishedTagName, SymfonyStyle $io, Taskr $taskr) use ($c) {
  $io->title("Create tag ($publishedTagName)");
  chdir($c['srcDir']);
  $taskr->passthru('git tag -f {{0|s}}', [$publishedTagName]);
});

$c['app']->command("build $commonOptions", function (SymfonyStyle $io, Taskr $taskr) use ($c) {
  $io->title('Build PHAR');
  chdir($c['srcDir']);
  $taskr->passthru('bash ./scripts/build.sh');
});

$c['app']->command("sign $commonOptions", function (SymfonyStyle $io, Taskr $taskr, \Crypt_GPG $gpg, $input) use ($c) {
  $io->title('Generate checksum and GPG signature');
  ['Init', $c['srcDir'], $c['distDir'], $c['publishedPharName']];
  chdir($c['distDir']);

  $pharFile = $c['publishedPharName'];
  $sha256File = preg_replace(';\.phar$;', '.SHA256SUMS', $pharFile);

  $taskr->passthru('cp {{0|s}} {{1|s}}', [$c['boxOutputPhar'], $pharFile]);
  $taskr->passthru('sha256sum {{0|s}} > {{1|s}}', [$pharFile, $sha256File]);

  $io->writeln("Sign $pharFile ($pharFile.asc)");
  if (!$input->getOption('dry-run')) {
    $gpg->signFile($pharFile, "$pharFile.asc", \Crypt_GPG::SIGN_MODE_DETACHED);
    assertThat(!empty($gpg->verifyFile($pharFile, file_get_contents("$pharFile.asc"))), "$pharFile should have valid signature");
  }
});

$c['app']->command("upload $commonOptions", function ($publishedTagName, SymfonyStyle $io, Taskr $taskr, Credentials $cred) use ($c) {
  $io->title("Upload code and build artifacts");
  ['Init', $c['srcDir'], $c['ghRepo'], $c['distDir'], $c['publishedPharName']];
  chdir($c['srcDir']);

  $vars = [
    'GCLOUD' => $c['gcloudUrl'],
    'GH_TOKEN' => 'GH_TOKEN=' . $cred->get('GH_TOKEN', $c['ghRepo']),
    'VER' => $publishedTagName,
    'REPO' => $c['ghRepo'],
    'DIST' => $c['distDir'],
    'PHAR' => $c['distDir'] . '/' . $c['publishedPharName'],
    'PHAR_NAME' => $c['publishedPharName'],
    'TOOL_NAME' => basename($c['boxOutputPhar']),
  ];

  $io->section('Check connections');
  $taskr->run('gsutil ls {{GCLOUD|s}}', $vars);
  $taskr->run('{{GH_TOKEN|s}} gh release list', $vars);

  $io->section('Send source-code to Github');
  $taskr->passthru('git push -f origin {{VER|s}}', $vars);

  $io->section('Send binaries to Github');
  $taskr->passthru('{{GH_TOKEN|s}} gh release create {{VER|s}} --repo {{REPO|s}} --generate-notes', $vars);
  $taskr->passthru('{{GH_TOKEN|s}} gh release upload {{VER|s}} --repo {{REPO|s}} --clobber {{DIST|s}}/*', $vars);

  $io->section('Send binaries to Google Cloud Storage');
  $taskr->passthru('gsutil cp {{DIST|s}}/* {{GCLOUD|s}}/', $vars);
  if (preg_match(';^v\d;', $publishedTagName)) {
    // Finalize: "mytool-1.2.3.phar" will be the default "mytool.phar"
    $suffixes = ['.phar', '.phar.asc', '.SHA256SUMS'];
    foreach ($suffixes as $suffix) {
      $taskr->passthru('gsutil cp {{GCLOUD|s}}/{{OLD_NAME}} {{GCLOUD|s}}/{{NEW_NAME}}', $vars + [
        'OLD_NAME' => preg_replace(';\.phar$;', $suffix, $c['publishedPharName']),
        'NEW_NAME' => preg_replace(';\.phar$;', $suffix, basename($c['boxOutputPhar'])),
      ]);
    }
  }
});

$c['app']->command("tips $commonOptions", function (SymfonyStyle $io) use ($c) {
  $io->title('Tips');
  $cleanup = sprintf('%s clean', basename(__FILE__));
  $io->writeln("Cleanup temp files: <comment>$cleanup</comment>");
  $url = sprintf('https://github.com/%s/releases/edit/%s', $c['ghRepo'], $c['publishedTagName']);
  $io->writeln("Update release notes: <comment>$url</comment>");
});

$c['app']->command("clean $globalOptions", function (SymfonyStyle $io, Taskr $taskr) use ($c) {
  $io->title('Clean build directory');
  ['Init', $c['srcDir'], $c['buildDir'], $c['boxOutputPhar']];
  chdir($c['srcDir']);

  $taskr->passthru('rm -rf {{0|@s}}', [[$c['buildDir'], $c['boxOutputPhar']]]);
});

###############################################################################
## Go!

$c['app']->run();
