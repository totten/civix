(function(angular, $, _) {
  // "<?php echo $dirNameCamel ?>" is a basic skeletal directive.
  // Example usage: <div <?php echo $dirNameHyp ?>="{foo: 1, bar: 2}"></div>
  angular.module('<?php echo $angularModuleName ?>').directive('<?php echo $dirNameCamel ?>', function() {
    return {
      restrict: 'AE',
      templateUrl: '<?php echo $htmlName ?>',
      scope: {
        <?php echo $dirNameCamel ?>: '='
      },
      link: function($scope, $el, $attr) {
        var ts = $scope.ts = CRM.ts('<?php echo $tsDomain ?>');
        $scope.$watch('<?php echo $dirNameCamel ?>', function(newValue){
          $scope.myOptions = newValue;
        });
      }
    };
  });
})(angular, CRM.$, CRM._);
