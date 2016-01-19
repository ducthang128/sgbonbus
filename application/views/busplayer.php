<html>
<head>
    <title>BES - Ads Player</title>
    <script src="/bes/jquery-1.9.1.min.js"></script>
    <script src="/bes/fill_resize.js" type="text/javascript"></script>
    <style type="text/css">
    * {
    	margin: 0px;
    	padding: 0px;
    }
        html, body { margin:0; }
        .videoContainer video{ width: 100%; height:100%; }
    </style>
</head>
<body>
    <div class="videoContainer">
        <video id="myvideo" controls="" autoplay="" poster="/bes/logo.jpg" class="fill" style="position:fixed; z-index:-1000;">
            <source src="<?php if (isset($cliptoplay) && $cliptoplay != ''){echo $cliptoplay;} ?>">
            <script type="text/javascript">
              $(document).ready(function(){
                $('video').on('ended',function(){
                  <?php
                    if (isset($clipid) && $clipid != ''){
                        echo 'window.top.location.href = "/player/play/'.$clipid.'";';
                    } else{
                        echo 'window.top.location.href = "/"';
                    }
                  ?>
                });
              });
            </script>
        </video>
    </div>
</body>
</html>