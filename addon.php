<?php
/*
Plugin Name: Train-up! File Attachment questions
Plugin URI: http://wptrainup.co.uk/
Description: Trainees are required to upload one or more files
Author: @amk221
Version: 0.0.3
License: GPL2
*/

namespace TU;

require_once(ABSPATH . 'wp-admin/includes/file.php');

class File_attachment_questions_addon {

  /**
   * __construct
   *
   * Listen to the filters that the Train-Up! plugin provides, and latch on,
   * inserting the new functionality where needed.
   *
   * @access public
   */
  public function __construct() {
    $this->path = plugin_dir_path(__FILE__);
    $this->url  = plugin_dir_url(__FILE__);

    add_action('init', array($this, '_register_assets'));
    add_action('wp', array($this, '_handle_deletions'));
    add_action('tu_question_backend_assets', array($this, '_add_backend_assets'));
    add_action('tu_question_frontend_assets', array($this, '_add_frontend_assets'));
    add_filter('tu_question_types', array($this, '_add_type'));
    add_filter('tu_question_meta_boxes', array($this, '_add_meta_box'), 10, 2);
    add_action('tu_meta_box_file_attachment', array($this, '_meta_box'));
    add_action('tu_save_question_file_attachment', array($this, '_save_question'));
    add_filter('tu_render_answers_file_attachment', array($this, '_render_answers'), 10, 3);
    add_filter('tu_validate_answer_file_attachment', array($this, '_validate_answer'), 10, 3);
    add_filter('tu_saved_answer', array($this, '_saved_answer'), 10, 3);
    add_filter('tu_saved_answer_ajax', array($this, '_saved_answer_ajax'), 10, 5);
  }

  /**
   * _register_assets
   *
   * - Fired on `init`
   * - Register the scripts and styles for the front end backend file attachment
   *   questions add-on.
   *
   * @access public
   */
  public function _register_assets() {
    wp_register_style('tu_file_attachment_questions', "{$this->url}css/backend/file_attachment_questions.css");
    wp_register_style('tu_frontend_file_attachment_questions', "{$this->url}css/frontend/file_attachment_questions.css");
  }

  /**
   * _add_backend_assets
   *
   * - Fired when styles and scripts are enqueued in the backend
   * - Enqueue the styles for the file-attachments meta box
   *
   * @access public
   */
  public function _add_backend_assets() {
    wp_enqueue_style('tu_file_attachment_questions');
  }

  /**
   * _add_frontend_assets
   *
   * - Fired when styles and scripts are enqueued on the frontend
   * - Enqueue scripts and styles for the file attachments quesiton type
   *
   * @access public
   */
  public function _add_frontend_assets() {
    if (tu()->question->type === 'file_attachment') {
      wp_enqueue_script('tu_frontend_file_attachment_questions');
      wp_enqueue_style('tu_frontend_file_attachment_questions');
    }
  }

  /**
   * _add_type
   *
   * - Callback for when retrieving the hash of question types.
   * - Insert our new 'file_attachment' question type.
   *
   * @param mixed $types
   *
   * @access public
   *
   * @return array The altered types
   */
  public function _add_type($types) {
    $types['file_attachment'] = __('File attachment', 'trainup');

    return $types;
  }

  /**
   * _add_meta_box
   *
   * - Callback for when the meta boxes are defined for Question admin screens
   * - Define one for our custom Question type: file_attachment
   *
   * @param mixed $meta_boxes
   *
   * @access public
   *
   * @return array The altered meta boxes
   */
  public function _add_meta_box($meta_boxes) {
    $meta_boxes['file_attachment'] = array(
      'title'    => __('File attachment options', 'trainup'),
      'context'  => 'advanced',
      'priority' => 'default'
    );

    return $meta_boxes;
  }

  /**
   * _meta_box
   *
   * - Callback function for an action that is fired when the 'file_attachment'
   *   meta box is to be rendered.
   * - Echo out the view that lets the administrator configure this Question
   *
   * @access public
   */
  public function _meta_box() {
    echo new View("{$this->path}/view/meta_box", array(
      'amount' => get_post_meta(tu()->question->ID, 'tu_file_attachment_amount', true) ?: 1
    ));
  }

  /**
   * _save_question
   *
   * - Fired when an file_attachment question is saved
   * - Note: File attachment style questions have no correct answer, therefore
   *   at this point we don't need to set the tu_answers post meta unlike most
   *   other question types.
   * - Save how many files are allowed to be uploaded
   *
   * @param mixed $question
   *
   * @access public
   */
  public function _save_question($question) {
    $amount = isset($_POST['tu_file_attachment_amount']) ? $_POST['tu_file_attachment_amount'] : 1;

    update_post_meta($question->ID, 'tu_file_attachment_amount', $amount);
  }

  /**
   * _render_answers
   *
   * - Fired when the file_attachment-style question is shown
   * - Return the view that allows Trainees to upload their file attachments.
   *
   * @param mixed $content
   *
   * @access public
   *
   * @return string The altered content
   */
  public function _render_answers($content, $users_answer, $question) {
    $files    = $question->get_uploads(tu()->user);
    $existing = count($files);
    $allowed  = get_post_meta($question->ID, 'tu_file_attachment_amount', true);
    $amount   = $allowed - $existing;
    $data     = compact('users_answer', 'question', 'amount', 'files');

    return new View("{$this->path}/view/answers", $data);
  }

  /**
   * _save_answer
   *
   * - Fired when the user saves their answer to any question (not via AJAX)
   *
   * @param object $question The Question that the answer is for.
   * @param string $answer The default answer string from the submitted form
   *
   * @access public
   *
   * @return mixed Value.
   */
  public function _saved_answer($response, $question, $answer) {
    $files = isset($_FILES['tu_file_attachment']) ? $_FILES['tu_file_attachment'] : array();
    return $this->save_attachments($response, $question, $files);
  }

  /**
   * _save_answer_ajax
   *
   * - Fired when the user saves their answer to any question via AJAX
   *
   * @param object $question The Question that the answer is for.
   * @param string $answer The default answer string from the submitted form
   * @param object $form A hash of any other form data
   * @param array $files An array of uploaded files
   *
   * @access public
   *
   * @return The response from the upload attempt
   */
  public function _saved_answer_ajax($response, $question, $answer, $form, $files) {
    return $this->save_attachments($response, $question, $files);
  }

  /**
   * save_attachments
   *
   * - Fired when the user saves their answer to any Question either via AJAX
   *   or the 'normal' method.
   * - Make sure the Question is one that accepts file attachments.
   * - Upload the file using WordPress's API, then move it to our location.
   *
   * @param object $question The Question that the answer is for.
   * @param array $files An array of uploaded files
   *
   * @access public
   *
   * @return true if successful, otherwise a hash of info about 1 failure.
   */
  private function save_attachments($response, $question, $files) {
    if ($question->type !== 'file_attachment' || count($files) < 1) {
      return $response;
    }

    $existing = count($question->get_uploads(tu()->user));
    $allowed  = get_post_meta($question->ID, 'tu_file_attachment_amount', true);

    if ($existing >= $allowed) {
      $response = array(
        'type' => 'error',
        'msg'  => __('Maximum amount of files already uploaded', 'trainup')
      );
      return $response;
    }

    $path      = $question->get_upload_path(tu()->user);
    $overrides = array('test_form' => false);

    @mkdir($path, 0777, true);

    foreach ($files['name'] as $i => $file_name) {
      if (!$file_name) continue;

      $file = array();

      foreach (array_keys($files) as $key) {
        $file[$key] = $files[$key][$i];
      }

      $result = wp_handle_upload($file, $overrides);

      if (isset($result['error'])) {
        $response = array(
          'type' => 'error',
          'msg'  => "{$result['error']}\n{$file_name}"
        );
      } else {
        $file_name = sanitize_file_name($file_name);
        copy($result['file'], "{$path}/{$file_name}");
        unlink($result['file']);
      }
    }

    return $response;
  }

  /**
   * _handle_deletions
   *
   * - Fired on `wp`
   * - Listen out for requests to delete a specific file.
   * - The deletion path contains the user ID, so only the logged in user
   *   can delete their own files.
   * - Redirect back to the question again to remove the query string
   *
   * @access public
   */
  public function _handle_deletions() {
    $can_delete = (
      isset(tu()->question) &&
      tu()->question->type === 'file_attachment' &&
      isset($_REQUEST['tu_action']) &&
      $_REQUEST['tu_action'] === 'delete_file'
    );

    if (!$can_delete) return;

    $files     = tu()->question->get_uploads(tu()->user);
    $file_name = null;

    foreach ($files as $file) {
      $file_name = basename($file);
      if (sanitize_file_name($_REQUEST['tu_file']) === $file_name) {
        unlink($file);
      }
    }

    if ($file_name) {
      $success = sprintf(__('%1$s deleted', 'trainup'), $file_name);
      tu()->message->set_flash('success', $success);

      tu()->question->go_to();
    }
  }

  /**
   * _validate_answer
   *
   * - Fired when an file_attachment question is validated
   * - Return null because this question type cannot be considered true or
   *   false. It has to be judged by a moderator and the percentage score
   *   manually set.
   *
   * @param mixed $correct Whether or not the answer is correct
   * @param mixed $users_answer The user's attempted answer
   * @param mixed $question The question this answer is for
   *
   * @access public
   *
   * @return null
   */
  public function _validate_answer($correct, $users_answer, $question) {
    return null;
  }

}


add_action('plugins_loaded', function() {
  new File_attachment_questions_addon;
});
