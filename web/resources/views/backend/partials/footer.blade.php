</div>
    <script src="{{static_asset('backend')}}/vendor/jquery/jquery-3.3.1.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

    <script src="{{static_asset('backend')}}/vendor/bootstrap-five/bootstrap.min.js"></script>
    <script src="{{static_asset('backend')}}/vendor/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="{{static_asset('backend')}}/vendor/slimscroll/jquery.slimscroll.js"></script>
    <script src="{{static_asset('backend')}}/libs/js/main-js.js"></script>
    <script src="{{static_asset('backend')}}/vendor/charts/sparkline/jquery.sparkline.js"></script>
    <script src="{{static_asset('backend')}}/vendor/charts/morris-bundle/raphael.min.js"></script>
    <script src="{{static_asset('backend')}}/vendor/charts/morris-bundle/morris.js"></script>
    <script src="{{static_asset('backend')}}/vendor/charts/c3charts/c3.min.js"></script>
    <script src="{{static_asset('backend')}}/vendor/charts/c3charts/d3-5.4.0.min.js"></script>
    <script src="{{static_asset('backend')}}/libs/js/datepicker.min.js"></script>
    <script src="{{static_asset('backend')}}/libs/js/custom.js"></script>
    <script src="{{static_asset('backend')}}/js/dynamic-modal.js"></script>
    <script src="{{static_asset('backend')}}/js/lang.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> 
    <script src="{{ static_asset('backend/vendor') }}/toastr/toastr.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 
    <script type="text/javascript">   
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    <script>var yes = "{{ __('delete.yes') }}";</script>
    <script>var cancel = "{{ __('delete.cancel') }}";</script>

    <script type="text/javascript">
        "use strict";
        $(function(){
            $('.demo-login-btn').click(function(){
                $('#email').attr('value',$(this).data('email'));
                $('#password').attr('value',$(this).data('password'));
                if($(this).data('merchant')){
                    $('#merchant').attr('checked',true);
                }else{
                    $('#merchant').attr('checked',false);
                }
            });
   
        });
    </script>
@stack('scripts')

{{-- S22 — le push navigateur du back-office est retiré (décision D12).

     Ce qui vivait ici inscrivait le navigateur de chaque agent au projet
     Firebase **de l'éditeur** (`we-courier-81101`, clés en clair dans la page),
     demandait la permission de notifier à chaque chargement, et envoyait
     ensuite par l'API FCM « legacy », arrêtée par Google en juin 2024 : la
     permission était réclamée pour un canal qui ne pouvait rien livrer.

     Le fil et les écrans du back-office ne changent pas. Pour un vrai push
     navigateur, voir D12 : il faudra un projet maîtrisé par le transporteur
     (FCM v1) ou du Web Push standard (VAPID), et un service worker à nous. --}}

    {!! Toastr::message() !!}
</body>
</html>
