<div id="media-item-[+attachment_id+]">

	<div width="400px">
		<span class="button" onclick="javascript:send_back_to_wp('[+attachment_id+]','[+preview_html+]')">[+select_label+]</span>
		[+thumbnail_html+]		
		<span class="post_selector_title">[+post_title+]</span>
		<span class="post_selector_toggler" onclick="javascript:toggle_image_detail('media-detail-[+attachment_id+]');">[+show_hide_label+]</span>

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
						<p><strong>[+filename_label+]:</strong> [+filename+]<br/>
						<strong>[+mime_type_label+]:</strong> [+post_mime_type+]<br/>
						<strong>[+upload_date_label+]:</strong> [+post_modified+]<br/>
						[+dimensions+]
						</p>
						<p><a href='[+attachment_url+]' target="_blank">[+view_original_label+]</a></p>
					</td>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
	
</div>
