angular.module('DxHealth.pcontrollers', [])

.controller('DxMateCtrl', function($scope, $rootScope, $ionicLoading, $http, $stateParams, $state, $ionicPlatform, $filter, $ionicModal, $ionicPopup) {
  $scope.showtab1 = true;
  $scope.showtab2 = false;
  $scope.showtab3 = true;
  $scope.showtab4 = false;
  $scope.showtab5 = true;
  $scope.showtab6 = false;
  $scope.selectedSymptom = [];
  $scope.evidence = [];
  $scope.symptoms = [];
  $scope.u={};


  $scope.keyfunc = function() {
    if($scope.u.query1.length>=3)
    {
      $scope.showLoadingIcon = true;
      var text= $scope.u.query1;
      var header = {
       'Content-Type': 'application/json ',
       'Accept': 'application/json'
     };
     $http({
      method: 'POST',
      url: 'http://rxhealth.esy.es/rxhealth/getsymptombydata.php',
      data: {'query':$scope.u.query1},
      headers: header
    })
      .success(function(data) {
        $scope.querylist = [];
        for (i = 0; i < data.Status.Result.length; i++) {
          $scope.querylist.push({
            'id': data.Status.Result[i].SymptomID,
            'name': data.Status.Result[i].SymptomName
          })
        }
        console.dir($scope.querylist);
        $scope.showLoadingIcon = false;
      }).error(function(data) {
        console.dir(data);
      });
    }
    else
    {
      $scope.querylist=[];
    }
  };

  $scope.next = function() {
    if($scope.u.age>=1 && $scope.u.age<=120)
    {
      var evidence = [];
      for(i=0;i<$scope.evidence.length;i++)
      {
        evidence.push({'id': $scope.evidence[i].id, 'choice_id': $scope.evidence[i].choice_id});
      }
      $ionicLoading.show({
        template: 'Loading...',
        noBackdrop: true
      });
      if($scope.u.gender == true)
      {
        $scope.sex= "female";
      }
      else
      {
        $scope.sex= "male";
      }
      var data = ({
        "sex": $scope.sex,
        "age": $scope.u.age,
        "evidence": evidence
      });
      var header = {
       'Content-Type': 'application/json ',
       'Accept': 'application/json',
       'app_id': 'e77c6784',
       'app_key': 'b8d453160b8da616fb165e657fcd14e8'
     };
     $http({
      method: 'POST',
      url: 'https://api.infermedica.com/v2/diagnosis',
      data: data,
      headers: header
    })

     .success(function(data){
      $scope.conditions = [];
      $scope.matchdata = data;
//      if($scope.symptoms.length==0)

      for(i=0;i<$scope.matchdata.conditions.length;i++)
      {
        if($scope.condition==$scope.matchdata.conditions[i].name)
          $scope.conditions.push({'ConditionID':$scope.matchdata.conditions[i].id, 'ConditionName':$scope.matchdata.conditions[i].name, 'CondProb':parseFloat($scope.matchdata.conditions[i].probability*100).toFixed(2), 'match':1});
        else
          $scope.conditions.push({'ConditionID':$scope.matchdata.conditions[i].id, 'ConditionName':$scope.matchdata.conditions[i].name, 'CondProb':parseFloat($scope.matchdata.conditions[i].probability*100).toFixed(2), 'match':0});
      }
      $scope.checkmatch();
             //return data
           }).error(function(data, status, headers, config){
            //$scope.data.error={message: error, status: status};
            alert("error"+ data);
            $ionicLoading.hide();
          });
         }
         else
         {
          $ionicLoading.hide();
          $ionicPopup.alert({
           title: 'Message',
           template: 'Please enter valid age to get the results!'
         });
        }
      };

      $scope.setModelSymptom = function (item) {
        $scope.evidence.push({'id':item.id, 'name':item.name, 'choice_id':"present", 'match':0});
        $scope.querylist = [];
        $scope.u.query1 = "";
        $scope.next();
      };

      $scope.add = function (item) {
        $scope.evidence.push({'id':item.SymptomID, 'name':item.SymptomName, 'choice_id':"present", 'match':0});
        $scope.next();
      };

      $scope.add2 = function (item) {
              $scope.evidence.push({'id':item.id, 'name':item.name, 'choice_id':"present", 'match':0});
              $scope.next();
            };

      $scope.present = function (item) {
        var index = $scope.evidence.indexOf(item);
        var newitem = { id: item.id, name:item.name, choice_id: "present"};
        $scope.evidence[index]=newitem;
        $scope.next();
      };

      $scope.absent = function (item) {
        var index = $scope.evidence.indexOf(item);
        var newitem = { id: item.id, name:item.name, choice_id: "absent"};
        $scope.evidence[index]=newitem;
        $scope.next();
      };

      $scope.unknown = function (item) {
        var index = $scope.evidence.indexOf(item);
        var newitem = { id: item.id, name:item.name, choice_id: "unknown"};
        $scope.evidence[index]=newitem;
        $scope.next();
      };

      $scope.onSymptomDelete = function(item) {
        $scope.evidence.splice($scope.evidence.indexOf(item), 1);
        $scope.next();
      };

    $scope.getmatch = function(item) {

    $scope.condition = item.ConditionName;

//    $scope.conditions[index].match=1;
      var header = {
       'Content-Type': 'application/json ',
       'Accept': 'application/json'
     };
     $http({
      method: 'POST',
      url: 'http://rxhealth.esy.es/rxhealth/getmatch.php',
      data: {'ConditionID':item.ConditionID},
      headers: header
    })

     .success(function(data){
      $scope.symptoms = [];
      $scope.getmatchdata = data.Status.Match;
      if($scope.getmatchdata.length>0)
      {
        $scope.condition = item.ConditionName;
      }
      else
      {
        $scope.condition = "We are constantly updating our data, Detailed list of symptoms of this Condition will be available soon";
        for(i=0;i<$scope.conditions.length;i++)
          $scope.conditions[i].match=0;
        for(i=0;i<$scope.evidence.length;i++)
          $scope.evidence[i].match=0;
      }

      for(i=0;i<$scope.getmatchdata.length;i++)
      {
        $scope.symptoms.push({'SymptomID':$scope.getmatchdata[i].SymptomID, 'SymptomName':$scope.getmatchdata[i].SymptomName, 'match':0});
      }
      $scope.checkmatch();
      $ionicLoading.hide();
             //return data
           }).error(function(data, status, headers, config){
            //$scope.data.error={message: error, status: status};
            alert("error"+ data);
            $ionicLoading.hide();
          });
         };

    $scope.checkmatch = function () {
    $ionicLoading.show({
            template: 'Loading...',
            noBackdrop: true
          });
     var header = {
           'Content-Type': 'application/json ',
           'Accept': 'application/json'
         };
         $http({
          method: 'POST',
          url: 'http://rxhealth.esy.es/rxhealth/getcommonrare.php',
          data: {'conditions':$scope.conditions},
          headers: header
        })

         .success(function(data){
         $scope.common = data.Status.Common;
         $scope.rare = data.Status.Rare;
          $ionicLoading.hide();
                 //return data
               }).error(function(data, status, headers, config){
                //$scope.data.error={message: error, status: status};
                alert("error"+ data);
                $ionicLoading.hide();
              });
          for(i=0;i<$scope.evidence.length;i++)
            {
              for(j=0;j<$scope.symptoms.length;j++)
              {
                  $scope.evidence[i].match=0;
                  $scope.symptoms[j].match=0;
              }
            }
            for(i=0;i<$scope.evidence.length;i++)
            {
              for(j=0;j<$scope.symptoms.length;j++)
              {
                if($scope.evidence[i].id==$scope.symptoms[j].SymptomID)
                {
                  $scope.evidence[i].match=1;
                  $scope.symptoms[j].match=1;
                }
              }
            }
          };

    })

.filter('reverse', function() {
  return function(items) {
    return items.slice().reverse();
  };

})
