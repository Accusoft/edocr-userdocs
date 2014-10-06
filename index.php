<!DOCTYPE html>
<html lang="en-us">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>edocr Document Listing</title>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta content="black" name="apple-mobile-web-app-status-bar-style">
    <!-- Basic Styles -->
    <link href="css/bootstrap.css" media="screen" rel="stylesheet" type="text/css">
    <link href="css/style.css" rel="stylesheet" type="text/css">
    <script src="//staging2.livestax.com/assets/livestax-0.1.0.js"></script>
  </head>
  <body>
    <div class="content twitter">
        <div class="row">
					<div class="col-md-12">
            <form class="form-horizontal">
              <div class="form-group">
                <label for="inputEmail" class="col-md-4 control-label">Enter Email Address of your account</label>
                <div class="col-md-4">
                  <input type="text" class="form-control" name="mail_address" id="mail_address" placeholder="Email">
                </div>
              </div>
              <div class="form-group">
                <div class="col-md-8 col-md-offset-4">
                  <button type="submit" class="btn btn-primary" id="sumit_button">Submit</button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <div id="document_list"></div>
    </div>
    <script src="js/jquery.min.js"></script>
		<script type="text/javascript">
    $('#sumit_button').click(function(e){
        e.preventDefault();
				$('#document_list').html('<div id="loading"> </div>');
				var mail_address = $('#mail_address').val();
        $.ajax({
            type: "POST",
            url: "document_list.php",
            data: {mail_address: mail_address},
            success: function(data){
                $('#document_list').html(data);
            }
        });
    });
    </script>
	</body>
</html>
