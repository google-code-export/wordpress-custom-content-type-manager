<div id="media-item-[+attachment_id+]">

	<div width="400px">
		<label for="media-option-[+attachment_id+]">		
			<span class="title">[+title+]</span>
		</label>
		<span class="button" onclick="javascript:update_selection('[+attachment_id+]','[+preview_html+]')">[+select_label+]</span>
		<span class="toggler" onclick="javascript:toggle_image_detail('media-detail-[+attachment_id+]');">[+show_hide+]</span>
	</div>
	
	<div id="media-detail-[+attachment_id+]" class="media_detail">
		<table class="media-detail">
			<thead class="media-item-info" id="media-head-[+attachment_id+]">
				<tr valign='top'>
					<td class="A1B1" id="thumbnail-head-[+attachment_id+]">
						<p>
							[+medium_html+]
						</p>
					</td>
					<td class="media_info">
						<p><strong>[+filename_label+]:</strong> [+filename+]</p>
						<p><strong>[+mime_type_label+]:</strong> [+mime_type+]</p>	
						<p><strong>[+upload_date_label+]:</strong> [+upload_date+]</p>
						[+dimensions+]
						<p><a href='[+attachment_url+]' target="_blank">[+view_original_label+]</a></p>
					</td>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
	
</div>