$(document).ready(function(){
    "use strict";   
      const swiper = new Swiper('.swiper', {
        speed: 400,
        spaceBetween: 100,   
        autoplay: {
          delay: 5000,
        },
        parallax:true,
        pagination:{
          el: ".swiper-pagination",
          clickable: true,
        },
        breakpoints: {
          // when window width is >= 320px
          320: {
            slidesPerView: 2,
            spaceBetween: 20
          },
          // when window width is >= 480px
          480: {
            slidesPerView: 3,
            spaceBetween: 30
          },
          // when window width is >= 640px
          640: {
            slidesPerView: 4,
            spaceBetween: 40
          },
          // when window width is >= 991px
          991: {
            slidesPerView: 8,
            spaceBetween: 40
          }
        }
      });

      
      var el = $('.odometer'); 
      el.each(function(){ 
         
          window.odometerOptions = {
            auto: false, // Don't automatically initialize everything with class 'odometer'
            selector: '.odometer', // Change the selector used to automatically find things to be animated
            format: '(,ddd).dd', // Change how digit groups are formatted, and how many digits are shown after the decimal point
            duration: 2000, // Change how long the javascript expects the CSS animation to take
            animation: 'count' // Count is a simpler animation method which just increments the value,
                              // use it when you're looking for something more subtle.
         
          };
          var countNumber = parseInt($(this).attr("data-count")).toFixed(2); 
          var k = countNumber/1000; 
          var lakh    = k/100; 
          var million = lakh/10;  
          if(million >=1){
            var countPosition = 'M<font style="font-size:25px">+</font>';
            var number = million;
          }else if(lakh >=1){
            var countPosition = 'L<font style="font-size:25px">+</font>';
            var number = lakh;

          }else if(k >=1){
            var countPosition = 'K<font style="font-size:25px">+</font>';
            var number = k;
          }else{
            var countPosition = '<font style="font-size:25px">+</font>';
            var number = countNumber;
          } 
          $(this).html(parseInt(number)); 
          $(this).closest('h2').find('.odometer-position').html(countPosition);
           
      });
  
});
