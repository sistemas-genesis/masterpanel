var app = angular.module('app',[]);

app.controller('main', ['$scope', '$http', async ($scope, $http) => {
    $scope.filter = {
      desde:moment().subtract(1, "days").format('YYYY-MM-DD'),
      hasta:moment().format('YYYY-MM-DD')
    };
    $scope.sucursales = {};
    $scope.empresa ={};
    $scope.detalle={};
    $scope.tabla=[];
    $scope.estado_deuda=[];
    $scope.opciones=[
      {
        id:0,
        value:'Sin deuda'
      },
      {
        id:1,
        value:'Notificacion'
      },
      {
        id:2,
        value:'No pago'
      },
      {
        id:3,
        value:'Corte'
      }
    ]

    $scope.setSucursal = (i)=>{
      $scope.libs.flag=false;
      $scope.empresa=i;
    }

    $scope.verDetalle = (i) => {
      $scope.libs.flag=true;
      $scope.tabla=[];
      $('#form').collapse('hide')
      $http.post('controlador/pacontrol/get/?op=detalle',{params:$scope.libs.params,empresa:$scope.empresa,filtros:$scope.filter})
      .success(res => {
        if (!res.success)
          toastr.warning(res.msg, '', 'warning');
        else {
          $scope.detalle = res;
          $scope.opciones.forEach(i=>{
            if (res.estado_deuda==i.id) {
              $scope.estado_deuda=i
            }
          })
          $scope.valor_deuda=parseFloat(res.valor_deuda);
          $scope.notificacion_deuda=res.notificacion_deuda;
          let labels =[]; let dataset1=[]; let dataset2=[]; let dataset3=[]; let labels2=[]; let dataset4=[]; let labels3=[]; let dataset5=[];
          $scope.detalle.ventasgra.forEach((i,index)=>{
            labels.push(i.fecha);
            dataset1.push(i.sums.Total.toFixed(2));
            dataset2.push($scope.detalle.comprasgra[index].sums.Total.toFixed(2))
            dataset3.push($scope.detalle.ingresosgra[index].sums.Total.toFixed(2))
            $scope.tabla.push({fecha:i.fecha, ventas:i.sums.Total, compras:$scope.detalle.comprasgra[index].sums.Total, ingresos: $scope.detalle.ingresosgra[index].sums.Total})
          })

          $scope.libs.dibujar(labels,dataset1,dataset2,dataset3);

          labels2=['Comprobantes Ingreso','Comprobantes Egreso','Comprobantes Diarios','Notas de Credito','Notas de Debito']
          dataset4=[[parseInt(res.co_ingreso.conteo)], [parseInt(res.co_egreso.conteo)], [parseInt(res.comp_diarios.conteo)], [parseInt(res.notas_credito.conteo)], [parseInt(res.notas_debito.conteo)]]
          $scope.libs.dibujar2(labels2,dataset4);

          labels3=['Items','Clientes','Proveedores']
          dataset5=[[parseInt(res.items)], [parseInt(res.cliente)], [parseInt(res.proveedores)]]
          console.log(dataset5)
          $scope.libs.dibujar3(labels3,dataset5);
        }
      })
      .error(() => {
        toastr.error('No se pudo establecer la comunicación con el servidor, intente nuevamente');
      })
    };
    $scope.getNumbers = (i) => {
      $scope.tabla=[];
      $('#form').collapse('hide')
      $http.post('controlador/pacontrol/get/?op=numeros',{params:$scope.libs.params,empresa:$scope.empresa,filtros:$scope.filter})
      .success(res => {
        if (!res.success)
          toastr.warning(res.msg, '', 'warning');
        else {
          let clientes=res.clientes
          let proveedores=res.proveedores

          var retContent = [];
          var retString = '';
          clientes.forEach(function (elem, idx){
            var elemText = [];
            // console.log(Object.values(elem))
            retContent.push(`${Object.values(elem).join(',')}`);
          });
          proveedores.forEach(function (elem1, idx){
            var elemText1 = [];
            retContent.push(`${Object.values(elem1).join(',')}`);
          });
          retString = retContent.join('\r\n');
          var file = new Blob([retString], {type: 'text/plain'});
          var pom = document.createElement('a');
          filename='Clientes_Proveedores.csv'
          pom.setAttribute('href', window.URL.createObjectURL(file));
          pom.setAttribute('download', filename);

          pom.dataset.downloadurl = ['text/plain', pom.download, pom.href].join(':');
          pom.draggable = true; 
          pom.classList.add('dragout');
          pom.click();
        }
      })
      .error(() => {
        toastr.error('No se pudo establecer la comunicación con el servidor, intente nuevamente');
      })
    };
    $scope.saveDeuda= (i)=>{
      $http.post('controlador/pacontrol/edi/?op=actualiza',{params:$scope.libs.params, empresa:$scope.empresa, estado_deuda:$scope.estado_deuda, valor_deuda:$scope.valor_deuda, notificacion_deuda:$scope.notificacion_deuda, aplica_todas: $scope.aplica_todas})
      .success(res => {
        if (!res.success)
          toastr.warning(res.msg, '', 'warning');
        else {
          toastr.info(res.msg, '', 'warning');
          $scope.verDetalle();
        }
      })
      .error(() => {
        toastr.error('No se pudo establecer la comunicación con el servidor, intente nuevamente');
      })
    }
    $scope.libs = {
      flag:false,
      params:[],
      dibujar: (labels,i,j,k)=>{
        $("#ventas").remove();
        $("#div-chart").append('<canvas id="ventas" style="max-width: 800px;max-heigth: 400px"></canvas>');
        if (myChart) {
          myChart.destroy()
        }
        var ctx= document.getElementById('ventas').getContext("2d");
        var myChart = new Chart(ctx,{
          type:"line",
          data:{
              labels: labels,
              datasets:[
              {
                label:'Ventas',
                data: i,
                borderColor: 'green',
                fill:false,
                tension:0
              },
              {
                label:'Compras',
                data:j,
                borderColor: 'red',
                fill:false,
                tension:0
              },
              {
                label:'Ingresos',
                data:k,
                borderColor: 'salmon',
                fill:false,
                tension:0
              }
              ]
          },
          options:{
              scales:{
                  yAxes:[{
                          ticks:{
                              beginAtZero:true
                          }
                  }]
              }
          }
        });
      },
      dibujar2: (labels,data)=>{
        $("#comprobantes").remove();
        $("#div-chart-doc").append('<canvas id="comprobantes" style="max-width: 800px;max-heigth: 400px"></canvas>');
        if (myChart) {
          myChart.destroy()
        }
        var ctx= document.getElementById('comprobantes').getContext("2d");
        var myChart = new Chart(ctx,{
          type:"bar",
          data:{
              // labels: labels,
              datasets:[
              {
                label:'Ingreso',
                data: data[0],
                backgroundColor: ['green'],
              },
              {
                label:'Egreso',
                data: data[1],
                backgroundColor: ['red'],
              },
              {
                label:'Diarios',
                data: data[2],
                backgroundColor: ['salmon'],
              },
              {
                label:'Credito',
                data: data[3],
                backgroundColor: ['purple'],
              },
              {
                label:'Debito',
                data: data[4],
                backgroundColor: ['orange'],
              },
              ]
          },
          options:{
            scales:{
              yAxes:[{
                ticks:{
                    beginAtZero:true
                }
              }]
            },
            tooltips:{
              callbacks:{
                title:function(tooltipitem, data){
                  title = ''
                  return title;
                }
              }
            }
          }
        });
      },
      dibujar3: (labels,data)=>{
        $("#varios").remove();
        $("#div-chart-var").append('<canvas id="varios" style="max-width: 800px;max-heigth: 400px"></canvas>');
        if (myChart) {
          myChart.destroy()
        }
        var ctx= document.getElementById('varios').getContext("2d");
        var myChart = new Chart(ctx,{
          type:"bar",
          data:{
              datasets:[
              {
                label:'Items',
                data: data[0],
                backgroundColor: ['skyblue'],
              },
              {
                label:'Clientes',
                data: data[1],
                backgroundColor: ['yellow'],
              },
              {
                label:'Proveedores',
                data: data[2],
                backgroundColor: ['gray'],
              },
              ]
          },
          options:{
            scales:{
              yAxes:[{
                ticks:{
                    beginAtZero:true
                }
              }]
            },
            tooltips:{
              callbacks:{
                title:function(tooltipitem, data){
                  title = ''
                  return title;
                }
              }
            }
          }
        });
      },
      get: i => {
        return new Promise(resolve => {
          $http.get('controlador/pacontrol/get/?op=empresas')
            .success(res => {
              if (!res.success)
                toastr.warning(res.msg, '', 'warning');
              else {
                $scope.datum = res.data;
              }
            })
            .error(() => {
              toastr.error('No se pudo establecer la comunicación con el servidor, intente nuevamente');
            })
        })
      },
      getdata: j=>{
        $scope.libs.params=j;
        $scope.sucursales={};
        $scope.filter = {
          desde:moment().subtract(1, "days").format('YYYY-MM-DD'),
          hasta:moment().format('YYYY-MM-DD')
        };
        $scope.detalle={};
        $scope.libs.flag=false;
        $scope.empresapa= j.nombre;
        $('#logo1').css('display','none');
        $('#contenido-collapse').css('visibility','visible');
        $http.post('controlador/pacontrol/get/?op=sucursales',j)
            .success(res => {
              if (!res.success)
                toastr.warning(res.msg, '', 'warning');
              else {
                $scope.sucursales = res.datos;
                              }
            })
            .error(() => {
              toastr.error('No se pudo establecer la comunicación con el servidor, intente nuevamente');
            })
      },
      getdata2: k=>{
        $('#logo1').css('display','none');
        $('#contenido-collapse').css('visibility','visible');
        $http.get('controlador/pacontrol/get/?op=sucursales2&c='+k)
            .success(res => {
              if (!res.success)
                toastr.warning(res.msg, '', 'warning');
              else {
                $scope.sucursales = res.datos;
                $scope.desde = res.fecha;
                $scope.hasta = res.fecha;
                $scope.empresapa= res.empresa;
              }
            })
            .error(() => {
              toastr.error('No se pudo establecer la comunicación con el servidor, intente nuevamente');
            })
      }
    }
    await $scope.libs.get();
}]);

app.directive('loading', ['$http', function ($http) 
{
    var html = '<div id="loader-wrapper">  <div id="loader"></div>  <div class="loader-section section-left"></div>  <div class="loader-section section-right"></div></div>';
    return {
        restrict: 'E',
        replace: true,
        template: html,
        link: function (scope, element, attrs) 
        {      
            scope.isLoading = function () {return $http.pendingRequests.length > 0 && !scope.$eval('noLoading');};       
            scope.$watch(scope.isLoading, function (value) {value ? element.removeClass('ng-hide') : element.addClass('ng-hide')});
        }
    };
}]);