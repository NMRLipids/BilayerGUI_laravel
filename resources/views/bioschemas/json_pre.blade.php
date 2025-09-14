{{-- Toggle with a triangle --}}
<div style="margin-top:0.5rem;margin-bottom:0.5rem;">
    <span id="json-toggle-btn" 
          style="cursor:pointer;user-select:none;font-size:0.8em;color:#eeeeee;">⏵ View JSON</span>
</div>

<pre id="json-preview"
     style="display:none;margin-top:0.5rem;padding:0.5rem;background:#f3f4f6;font-size:0.8rem;border-radius:0.25rem;overflow-x:auto;">
{!! $entity['jsonLd'] !!}
</pre>

<script>
    $(document).ready(function(){
        $("#json-toggle-btn").click(function(){
            if($("#json-toggle-btn").text() === "⏵ View JSON") {
   	            $("#json-toggle-btn").text("⏷ Hide JSON") 
            } else {
     	        $("#json-toggle-btn").text("⏵ View JSON")
            }
            $("#json-preview").slideToggle("slow");
        }); 
    });
</script>

