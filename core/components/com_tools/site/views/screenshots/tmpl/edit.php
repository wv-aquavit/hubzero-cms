<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 HUBzero Foundation, LLC.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

// No direct access.
defined('_HZEXEC_') or die();

$size = getimagesize($this->upath . DS . $this->file);
$w = ($size[0] > 600) ? $size[0]/1.4444444 : $size[0];
$h = ($w != $size[0]) ? $size[1]/1.4444444 : $size[1];

$title = (count($this->shot) > 0 && isset($this->shot[0]->title)) ? $this->shot[0]->title : '';
?>
<?php if ($this->getError()) { ?>
	<p class="error"><?php echo implode('<br />', $this->getErrors()); ?></p>
<?php } ?>
	<div class="ss_pop">
		<div>
			<img src="<?php echo $this->wpath . '/' . $this->file; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" alt="" />
		</div>
		<form action="<?php echo Route::url('index.php?option=' . $this->option); ?>" name="hubForm" id="ss-pop-form" method="post" enctype="multipart/form-data">
			<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
			<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
			<input type="hidden" name="tmpl" value="component" />
			<input type="hidden" name="version" value="<?php echo $this->version; ?>" />
			<input type="hidden" name="pid" id="pid" value="<?php echo $this->pid; ?>" />
			<input type="hidden" name="path" id="path" value="<?php echo $this->upath; ?>" />
			<input type="hidden" name="filename" id="filename" value="<?php echo $this->file; ?>" />
			<input type="hidden" name="vid" id="vid" value="<?php echo $this->vid; ?>" />
			<input type="hidden" name="task" value="save" />
			<fieldset class="uploading">
				<label class="ss_title" for="ss_title">
					<?php echo Lang::txt('COM_TOOLS_SS_TITLE'); ?>:
					<input type="text" name="title" id="ss_title"  size="127" maxlength="127" value="<?php echo $this->escape($title); ?>" class="input_restricted" />
				</label>
				<input type="submit" id="ss_pop_save" value="<?php echo strtolower(Lang::txt('COM_TOOLS_SAVE')); ?>" />
			</fieldset>
 		</form>
	</div>