<footer class="text-center text-white navbar bg-primary">
    <!-- fixed-bottom-->
    <!-- Grid container -->
    <div class=" p-4 w-100">
        <!-- Section: Images -->
        <section>

            <div class="row justify-content-center align-items-center ">
                <span> Copyright &copy;{{ date('Y') }} - NMRlipids - Universidade de Santiago de Compostela, Universitetet i Bergen </span>
                <!--<div class="col-md-3 col-sm-12  justify-content-center  logos" style="max-width: 250px;">
           <a href="https://www.AAA.com"><img class="img-fluid"  src="{{ asset('storage/images/AAA.jpg') }}"/></a>
          </div>
        <div class="col-md-3 col-sm-12 justify-content-center   logos"  style="max-width: 250px;">
              <a href="https://www.AAA.com"><img class="img-fluid"  src="{{ asset('storage/images/AAA.jpg') }}"/></a>
        </div>
        <div class="col-md-3 col-sm-12  justify-content-center  logos"  style="max-width: 250px;">
           <a href="https://www.AAA.com"><img class="img-fluid"  src="{{ asset('storage/images/AAA.jpg') }}"/></a>
        </div>
      -->
            </div>

        </section>
        <!-- Section: Images -->
    </div>
    <!-- Grid container -->

</footer>
<!-- Bootstrap core JS
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
-->
<!-- SimpleLightbox plugin JS-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/SimpleLightbox/2.1.0/simpleLightbox.min.js"></script>
<!-- Core theme JS
<script src="storage/js/scripts.js"></script>
-->
<!--
<script type="text/javascript" src="{{ asset('storage/js/multislider.js') }}"></script>
-->


<script>
    $(document).ready(function() {

        console.log("cargando");
        $('a.portfolio-box').simpleLightbox();

        $('[id^=jmolApplet]').css('z-index', 1);

    });
</script>

</body>

</html>
