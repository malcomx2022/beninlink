
<script type="text/javascript" src="{{ static_asset('backend/vendor/calender/main.js') }}"></script>
<script>

  document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    if(calendarEl !=null){
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialDate: '{{ date("Y-m-d") }}',
        editable: true,
        selectable: true,
        businessHours: true,
        dayMaxEvents: true, // allow "more" link when too many events
        events: [
                @php if(calendarnewsoffer(date("Y-m-d")) !==null){  @endphp
            {
                title: 'News & Offer : {{  calendarnewsoffer(date("Y-m-d"))->title }}',
                start: '{{ date("Y-m-d") }}'
            }
            @php } @endphp
        ]
    });
    calendar.render();
}
  });

</script>
