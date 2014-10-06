		<?php
      session_start();
      require_once("phpEdocr.php");
      define(CONSUMER_KEY, "ba8532ea606d5c93a8a8b180954e9ea1");
      define(CONSUMER_SECRET, "d8e085de1ac97a0e07a01a42457fc5bf");
      
      $obj_phpEdocr = new phpEdocr(CONSUMER_KEY, CONSUMER_SECRET);
			
			$doc_count_api_response = $obj_phpEdocr->call_api_method(
        'edocr.getUserDocCount',
        array(
          "mail_address" =>	$_REQUEST['mail_address'],
        ),
        false,
        ($_REQUEST['http_method'] == "POST")? "POST" : "GET"
      );
			$doc_count_array = json_decode($doc_count_api_response);
			$all_docs = $doc_count_array->documents;
			
      $api_method = 'edocr.getUserLimitedDocList';
			$page = $_REQUEST['page'];
 			$mail_address = $_REQUEST['mail_address'];
      $send_page = $page;
			if(!$send_page){
			  $send_page = 1;
			}
			if($send_page <= 0){
			  $send_page = 1;
			}
      $api_response = $obj_phpEdocr->call_api_method(
        $api_method,
        array(
          "mail_address" =>	$_REQUEST['mail_address'],
					"page" =>	$send_page,
        ),
        false,
        ($_REQUEST['http_method'] == "POST")? "POST" : "GET"
      );
      $array = json_decode($api_response);
			$num_docs = $array->total;
			$total_pages = $all_docs;
			$adjacents = 3;
			$targetpage = "index.php";
			$limit = 10;
			if($page) 
				$start = ($page - 1) * $limit;
			else
				$start = 0;
				
			if ($page == 0) $page = 1;					
			$prev = $page - 1;							
			$next = $page + 1;							
			$lastpage = ceil($total_pages/$limit);		
			$lpm1 = $lastpage - 1;						
			
			$pagination = "";
			$page_class="";
			if($lastpage > 1)
			{	
				$pagination .= "<ul class=\"pagination\">";
				if ($page > 1) 
					$pagination.= "<li><a href=\"#\" title=\"$prev\">&laquo;</a></li>";
				else
					$pagination.= "<li class=\"disabled\"><a>&laquo;</a></li>";	
				
				if ($lastpage < 7 + ($adjacents * 2))	
				{	
					for ($counter = 1; $counter <= $lastpage; $counter++)
					{
						if($counter > 2){
							$page_class = "away-2";
						}
						if($counter > 3){
							$page_class = "away-3";
						}
						if($counter > 4){
							$page_class = "away-4";
						}
						if ($counter == $page)
							$pagination.= "<li class=\"active\"><a>$counter</a></li>";
						else
							$pagination.= "<li class=\"$page_class\"><a href=\"#\" title=\"$counter\">$counter</a></li>";					
					}
				}
				elseif($lastpage > 5 + ($adjacents * 2))	
				{
					if($page < 1 + ($adjacents * 2))		
					{
						for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
						{
							if($counter > 2){
								$page_class = "away-2";
							}
							if($counter > 3){
								$page_class = "away-3";
							}
							if($counter > 4){
								$page_class = "away-4";
							}
							if ($counter == $page)
								$pagination.= "<li class=\"active\"><a>$counter</a></li>";
							else
								$pagination.= "<li class=\"$page_class\"><a href=\"#\" title=\"$counter\">$counter</a></li>";					
						}
						$pagination.= "<li><a>...</a></li>";
						$pagination.= "<li class=\"$page_class\"><a href=\"#\" title=\"$lpm1\">$lpm1</a></li>";
						$pagination.= "<li><a href=\"#\" title=\"$lastpage\">$lastpage</a></li>";		
					}
					elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
					{
						$pagination.= "<li><a href=\"#\" title=\"1\">1</a></li>";
						$pagination.= "<li><a href=\"#\" title=\"2\">2</a></li>";
						$pagination.= "<li><a>...</a></li>";
						for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
						{
							if($counter > 2){
								$page_class = "away-2";
							}
							if($counter > 3){
								$page_class = "away-3";
							}
							if($counter > 4){
								$page_class = "away-4";
							}
							if ($counter == $page)
								$pagination.= "<li class=\"active\"><a>$counter</a></li>";
							else
								$pagination.= "<li class=\"$page_class\"><a href=\"#\" title=\"$counter\">$counter</a></li>";					
						}
						$pagination.= "<li><a>...</a></li>";
						$pagination.= "<li class=\"$page_class\"><a href=\"#\" title=\"$lpm1\">$lpm1</a></li>";
						$pagination.= "<li><a href=\"#\" title=\"$lastpage\">$lastpage</a></li>";		
					}
					else
					{
						$pagination.= "<li><a href=\"#\" title=\"1\">1</a></li>";
						$pagination.= "<li><a href=\"#\" title=\"2\">2</a></li>";
						$pagination.= "<li><a>...</a></li>";
						for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++)
						{
							if($counter > 2){
								$page_class = "away-2";
							}
							if($counter > 3){
								$page_class = "away-3";
							}
							if($counter > 4){
								$page_class = "away-4";
							}
							if ($counter == $page)
								$pagination.= "<li class=\"active\"><a>$counter</a></li>";
							else
								$pagination.= "<li class=\"$page_class\"><a href=\"#\" title=\"$counter\">$counter</a></li>";					
						}
					}
				}
				
				if ($page < $counter - 1) 
					$pagination.= "<li><a href=\"#\" title=\"$next\">&raquo;</a></li>";
				else
					$pagination.= "<li class=\"disabled\"><a>&raquo;</a></li>";
				$pagination.= "</ul>\n";		
			}
			if($num_docs){
				for($i = 0; $i < $num_docs; $i++){
					$doc_description = $array->document[$i]->description;
					if(strlen($doc_description) > 150) {
						$doc_description = substr($doc_description, 0, 150).' ...';
					}
				?>
				<div class="row">
					<div class="col-md-12">
						<?php 
						print '<div class="doc_thumb"><img src="'.$array->document[$i]->thumanail.'" /></div>';
						print '<div class="doc_data">
										<div class="data_wrapper">
											<div class="doc_title"><a href="'.$array->document[$i]->url.'">'.$array->document[$i]->title.'</a></div>
											<div class="doc_desciption">'.strip_tags($doc_description).'</div>
										</div>';
						print '</div>';
						?>
					</div>
				</div>
			<?php
				}
				print '<div class="row"><div class="col-md-12">'.$pagination.'</div></div>';
			}
			else if($mail_address && !$num_docs){
			  print '<div class="row"><div class="col-md-12">No documents found.</div></div>';
			}
			?>
		<script type="text/javascript">
        $('ul.pagination li a').click(function(e){
           e.preventDefault();
					 var page = $( this ).attr('title');
					 if(page){
				    $('#document_list').html('<div id="loading"> </div>');
            $.ajax({
                type: "POST",
                url: "document_list.php",
                data: {mail_address: '<?php print $mail_address;?>', page: page},
                success: function(data){
                    $('#document_list').html(data);
                }
            });
					}
        });
    </script>