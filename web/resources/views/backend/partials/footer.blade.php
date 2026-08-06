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
    <script src="https://www.gstatic.com/firebasejs/8.3.2/firebase.js"></script>
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

<script type="text/javascript">
    "use strict";
    $(document).ready(function() {
        var firebaseConfig = {
            apiKey: "AIzaSyDCthiio0WgX1F2CiVlw1Z-kWOKYYi6vQI",
            authDomain: "we-courier-81101.firebaseapp.com",
            projectId: "we-courier-81101",
            storageBucket: "we-courier-81101.appspot.com",
            messagingSenderId: "151878495365",
            appId: "1:151878495365:web:133d9bace19a4846260dec",
            measurementId: "G-V1K1HVXD5G"
        };

            firebase.initializeApp(firebaseConfig);
            const messaging = firebase.messaging();
            startFCM();
            function startFCM() {
                messaging.requestPermission()
                    .then(function () {
                        return messaging.getToken()
                    })
                    .then(function (response) {
                        var dataurl = '{{request()->is('/')}}'
                        if(dataurl == 1){
                            $.ajax({
                                url: '{{ route("notification-store.token") }}',
                                type: 'POST',
                                data: {
                                    token: response
                                },
                                dataType: 'JSON',
                                success: function (response) {
                                    console.log(response);
                                },
                                error: function (error) {
                                    console.log(error);
                                },
                            });
                        }
                    }).catch(function (error) {
                    console.log(error);
                });
            }

            messaging.onMessage(function(payload) {
                console.log(payload.notification);
                const title = payload.notification.title;
                const options = {
                    body: payload.notification.body,
                    icon: payload.notification.icon,
                };
                Swal.fire({
                    imageUrl:payload.notification.image,
                    title: title,
                    text: payload.notification.body,
                    position: 'top',
                    showOkButton: true,
                    showCancelButton: true,
                    confirmButtonText: yes,
                    cancelButtonText: cancel,
                }).then((result) => {
                    if (result.isConfirmed){
                        console.log('ok');
                    }
                })
                new Notification(title, options);
            });
    });
</script>

    {!! Toastr::message() !!}
</body>
</html>
