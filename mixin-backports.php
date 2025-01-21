<?php

/**
 * Mirror a select list of backports from `civicrm-core:mixin/`.
 */
return [
  'polyfill' => [
    'sha256' => 'fe28125744b6c78a62a3a365253295df0f15b6335cdc8c0e7ad28827625a0fa8',
    'remote' => 'https://raw.githubusercontent.com/civicrm/civicrm-core/4d32ebf95a5214cc5cea8a0d2578cb8d2c511746/mixin/polyfill.php',
    // ^^ This revision was technically merged in 6.0. However, it's basically NFC compared to the prior 5.58-era revision.
    'local' => 'extern/mixin/polyfill.php',
    'provided-by' => '5.45.beta1',
    'minimum' => '5.27', /* Compat may go back further; haven't tested */
  ],
  'afform-entity-php@1' => [
    'version' => '1.0.0',
    'sha256' => '1f737bc96e019dac459aade88206778cf141c59d322d5aa56c02ee4afb9eb649',
    'remote' => 'https://raw.githubusercontent.com/civicrm/civicrm-core/5.50.0/mixin/afform-entity-php@1/mixin.php',
    'local' => 'extern/mixin/afform-entity-php@1/mixin.php',
    'provided-by' => '5.50.beta1',
    'minimum' => '5.31', /* See #246. Scanner introduced as global (5.31) then became optional (5.50 mixin). Mixin should be enabled for 5.50 compat, but it's a technical nullop on 5.31. */
  ],
  'ang-php@1' => [
    'version' => '1.0.0',
    'sha256' => '7dc91fab620fc6ba74a5a00347a2d8f39846c97c8cf20cf4291a1002d3d9c245',
    'remote' => 'https://raw.githubusercontent.com/civicrm/civicrm-core/5.45.3/mixin/ang-php@1/mixin.php',
    'local' => 'extern/mixin/ang-php@1/mixin.php',
    'provided-by' => '5.45.beta1',
    'minimum' => '5.27', /* Compat may go back further; haven't tested */
  ],
  'case-xml@1' => [
    'version' => '1.0.0',
    'sha256' => '23a4f7d128b286c79acf20598a9017a0fac213115a64504d8f84c2096ce1f490',
    'remote' => 'https://raw.githubusercontent.com/civicrm/civicrm-core/5.45.3/mixin/case-xml@1/mixin.php',
    'local' => 'extern/mixin/case-xml@1/mixin.php',
    'provided-by' => '5.45.beta1',
    'minimum' => '5.27', /* Compat may go back further; haven't tested */
  ],
  'entity-types-php@1' => [
    'version' => '1.0.0',
    'sha256' => 'f8e10aac991b2b3acac269a1fca81f883a295908990687959e790885a2e410c2',
    'remote' => 'https://raw.githubusercontent.com/civicrm/civicrm-core/5.57.0/mixin/entity-types-php%401/mixin.php',
    'local' => 'extern/mixin/entity-types-php@1/mixin.php',
    'provided-by' => '5.57.beta1',
    'minimum' => '5.45',
  ],
  'entity-types-php@2' => [
    'version' => '2.0.0',
    'sha256' => 'ea98aa9ec3a4ee2c0c2f19cf264f20db344d4d25e2dc714e0ba45cccb1cf6c56',
    'remote' => 'https://raw.githubusercontent.com/civicrm/civicrm-core/5d5c59c9453fabf1c3f709c7b8e26c680312d9a8/mixin/entity-types-php@2/mixin.php',
    'local' => 'extern/mixin/entity-types-php@2/mixin.php',
    'provided-by' => '5.73',
    'minimum' => '5.45',
  ],
  'menu-xml@1' => [
    'version' => '1.0.0',
    'sha256' => '4f5be44d6764816b22d0a5cdc2e047cfd9ec4acf48e548f82bb20c05db933d0e',
    'remote' => 'https://raw.githubusercontent.com/civicrm/civicrm-core/5.45.3/mixin/menu-xml@1/mixin.php',
    'local' => 'extern/mixin/menu-xml@1/mixin.php',
    'provided-by' => '5.45.beta1',
    'minimum' => '5.27', /* Compat may go back further; haven't tested */
  ],
  'mgd-php@1' => [
    'version' => '1.0.0',
    'sha256' => '458fef1ae4b649bae7826bfc9f3b7d55c6a3f577d2eae4cbc1c520c48d6d4d7d',
    'remote' => 'https://raw.githubusercontent.com/civicrm/civicrm-core/5.45.3/mixin/mgd-php@1/mixin.php',
    'local' => 'extern/mixin/mgd-php@1/mixin.php',
    'provided-by' => '5.45.beta1',
    'minimum' => '5.27', /* Compat may go back further; haven't tested */
  ],
  'scan-classes@1' => [
    // scan-calsses is not meaningful to backport, but tracking metadata helps with admin.
    'version' => '1.0.0',
    'sha256' => '68b543079255d3d92773a5d75f5b033b3227b595337ece207f6dec865a54f0c4',
    'remote' => 'https://raw.githubusercontent.com/civicrm/civicrm-core/4b0558d911bfcbd81e1f5199b0eb0c837c7c8205/mixin/scan-classes@1/mixin.php',
    'local' => 'extern/mixin/scan-classes@1/mixin.php',
    'provided-by' => '5.51.beta2',
    'minimum' => '5.51', /* No point in deploying no systems that lack civicrm-core:693067e365915ce280217047009c9e87d70d0719 */
  ],
  'setting-admin@1' => [
    'version' => '1.0.1',
    'sha256' => 'b7b5209c88d07a483886f73f0f709fa510c5c35dc1a3e415cda56eb3ac9b8bf8',
    'remote' => 'file://' . __DIR__ . '/mixin-mods/setting-admin@1.0.1.mixin.php',
    'local' => 'extern/mixin/setting-admin@1/mixin.php',
    'provided-by' => '5.68.beta1',
    'minimum' => '5.27', /* civix ships a special backport from `./mixin-mods/` to provide broader compatibility. Compat may go back further; haven't tested. Mainline has higher requirements. */
  ],
  'setting-php@1' => [
    'version' => '1.0.0',
    'sha256' => '5ce236c1a1a63637ce5f0f4fe5bf7f21eaa06c750ca16c0fbf4dd792da0d23c9',
    'remote' => 'https://raw.githubusercontent.com/civicrm/civicrm-core/5.45.3/mixin/setting-php@1/mixin.php',
    'local' => 'extern/mixin/setting-php@1/mixin.php',
    'provided-by' => '5.45.beta1',
    'minimum' => '5.27', /* Compat may go back further; haven't tested */
  ],
  'smarty@1' => [
    // Newer variant. Prettier name.
    'version' => '1.0.3',
    'sha256' => 'a10fbe1508383b7a6e038416e4bb2204bcbf0484e9419b759e27985353c15c63',
    'remote' => 'https://raw.githubusercontent.com/civicrm/civicrm-core/5.74.0/mixin/smarty%401/mixin.php',
    'local' => 'extern/mixin/smarty@1/mixin.php',
    'provided-by' => '5.74',
    'minimum' => '5.27', /* Compat may go back to 5.25; only really tested 5.33 */
  ],
  'smarty-v2@1' => [
    // Original variant. Name was more specific than we actually need.
    'version' => '1.0.3',
    'sha256' => '06b5a9b69289d30221ecb0239c99341a4b661cc1d33ee7c9c48e2722402a2210',
    'remote' => 'https://raw.githubusercontent.com/civicrm/civicrm-core/5.74.0/mixin/smarty-v2%401/mixin.php',
    'local' => 'extern/mixin/smarty-v2@1/mixin.php',
    'provided-by' => '5.74',
    'minimum' => '5.27', /* Compat may go back to 5.25; only really tested 5.33 */
  ],
  'theme-php@1' => [
    'version' => '1.0.0',
    'sha256' => '2d4bd2442fde152c8f31805ac265c2249d5cf771185f1ac870fd1fcbb18db3ed',
    'remote' => 'https://raw.githubusercontent.com/civicrm/civicrm-core/5.45.3/mixin/theme-php@1/mixin.php',
    'local' => 'extern/mixin/theme-php@1/mixin.php',
    'provided-by' => '5.45.beta1',
    'minimum' => '5.27', /* Compat may go back further; haven't tested */
  ],
];
