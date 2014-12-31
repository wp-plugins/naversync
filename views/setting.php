<form action="?page=naver_xml_rpc&mode=update" method="post" id="saxmlrpcForm">
	<div class="">
		<p>네이버 블로그 글쓰기 API키를 입력해주세요.</p>

		<p>API키는 블로그 -&gt; 내메뉴 -&gt; 관리 -&gt; 메뉴글관리 -&gt; 글쓰기 API설정에 나오는 API연결 암호입니다.</p>
	</div>

	<table class="wp-list-table widefat fixed">
		<colgroup>
			<col style="width:25%;">
			<col style="width:75%;">
		</colgroup>
		<thead>
		<tr>
			<th colspan="2">Naver XmlRpc</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<th>네이버 블로그 ID</th>
			<td>
				<input type="text" name="id" value="<?php _e( $id ) ?>"/>

				<p>네이버 블로그 아이디를 입력해주세요.</p>
			</td>
		</tr>
		<tr>
			<th>네이버 블로그 글쓰기 api키</th>
			<td>
				<input type="text" name="api_key" value="<?php _e( $api_key ) ?>"/>

				<p>네이버 블로그에서 APIKEY를 확인한 후 입력해주세요.</p>
			</td>
		</tr>
		<tr>
			<th>사용여부</th>
			<td>
				<select name="api_use_yn" id="api_use_yn">
					<option value="Y" <?php if ($useYn == 'Y') : ?>selected="selected"<?php endif ?>>사용함</option>
					<option value="N" <?php if ($useYn == 'N') : ?>selected="selected"<?php endif ?>>사용안함</option>
				</select>

				<p>글 작성시 사용여부를 선택해주세요. 사용안함을 선택할경우 동작하지 않습니다.</p>
		</tr>
		<tr>
			<th>삭제기능 사용여부</th>
			<td>
				<select name="del_use_yn" id="del_use_yn">
					<option value="Y" <?php if ($del_useYn == 'Y') { ?>selected="selected"<?php } ?>>사용함</option>
					<option value="N" <?php if ($del_useYn == 'N') { ?>selected="selected"<?php } ?>>사용안함</option>
				</select>

				<p>워드프레스에서 글 삭제시 네이버 블로그에서도 글을 삭제할것인지를 선택해주세요. </p>
			</td>
		</tr>
		<tr>
			<th>고정카테고리</th>
			<td>
				<input type="text" name="fix_cate" value="<?php echo $fix_cate; ?>"/>

				<p>네이버에 고정 등록할 카테고리를 대소문자 구분하여 입력해주세요. 오타 발생시 적용되지 않습니다.</p>
			</td>
		</tr>
		</tbody>
	</table>

	<div class="sa_message">
		<?php if ( isset( $error_message ) && ! empty( $error_message ) ) : ?>
			<p><?php _e( $error_message ); ?></p>
		<?php endif; ?>
		<?php if ( isset( $error_message ) && empty( $error_message ) ) : ?>
			<p>
				<strong><?php _e( $user_info->nickname ) ?></strong>님 네이버 글쓰기 api에 연결되었습니다.
				<a class="button" href="<?php _e( $user_info->url ) ?>" target="_blank" style="margin-left:10px;">블로그
					가기</a>
			</p>
		<?php endif ?>
	</div>

	<div class="tablenav">
		<div class="alignright actions">
			<input type="submit" id="doaction" class="button button-big action" value="확인">
		</div>
	</div>
</form>