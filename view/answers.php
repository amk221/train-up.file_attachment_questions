<form class="tu-answers" action="" method="POST" enctype="multipart/form-data">
  <div class="tu-file-attachment-answers">

    <div class="tu-form-row">
      <div class="tu-form-label">
        <?php echo apply_filters('tu_form_label', __('Your answer:', 'trainup'), 'your_answer'); ?>
      </div>
      <div class="tu-form-inputs">
        <?php for ($i = 0; $i < $amount; $i++) { ?>
          <div class="tu-form-input tu-form-file">
            <input type="file" name="tu_file_attachment[<?php echo $i; ?>]">
          </div>
        <?php } ?>
      </div>
    </div>

    <?php if ($files) { ?>
      <div class="tu-form-row">
        <div class="tu-form-label">
          <?php echo apply_filters('tu_form_label', __('Attached files:', 'trainup'), 'attached_files'); ?>
        </div>
        <div class="tu-form-inputs">
          <ul class="tu-file-list">
            <?php foreach ($files as $file) { ?>
              <li>
                <a class="tu-download-file" href="<?php
                  echo add_query_arg(array(
                    'tu_action' => 'download_file',
                    'tu_file'   => urlencode(basename($file))
                  ), $question->url);
                  ?>"><?php
                  echo basename($file);
                ?></a>
                <span class="tu-delete-file">
                  (<a class="tu-delete-file-link" href="<?php
                    echo add_query_arg(array(
                      'tu_action' => 'delete_file',
                      'tu_file'   => urlencode(basename($file))
                    ), $question->url);
                    ?>"><?php
                    _e('Delete');
                  ?></a>)
                </span>
              </li>
            <?php } ?>
          </ul>
        </div>
      </div>
    <?php } ?>

    <div class="tu-form-row">
      <div class="tu-form-label"></div>
      <div class="tu-form-inputs">
        <div class="tu-form-input tu-form-button">
          <button type="submit">
            <?php echo apply_filters('tu_form_button', __('Save my answer', 'trainup'), 'save_answer'); ?>
          </button>
        </div>
      </div>
    </div>
    
  </div>
</form>