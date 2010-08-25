<?php

/**
 * My Application
 *
 * @copyright  Copyright (c) 2010 John Doe
 * @package    MyApplication
 */

use Nette\Application\AppForm,
	Nette\Forms\Form;

/**
 * Homepage presenter.
 *
 * @author     John Doe
 * @package    MyApplication
 */
class HomepagePresenter extends BasePresenter
{
	public function renderDefault()
	{
		$this->template->posts = PostsModel::fetchAll();
	}

	public function renderSingle($id)
	{
		if (!($post = PostsModel::fetchSingle($id))) {
			$this->redirect('default'); //article not found
		}
		$this->template->post = $post;
		$this->template->comments = CommentsModel::fetchAll($id);
	}

	public function createComponentCommentForm($name)
	{
		$form = new AppForm($this, $name);
		$form->addText('author', 'Jméno')
				->addRule(Form::FILLED, 'To se neumíš ani podepsat?!');
		$form->addTextArea('body', 'Komentář')
				->addRule(Form::FILLED, 'Komentář je povinný!');
		$form->addSubmit('send', 'Odeslat');
		$form->onSubmit[] = callback($this, 'commentFormSubmitted');
		return $form;
	}

	public function commentFormSubmitted(AppForm $form)
	{
		$data = $form->getValues();
		$data['date'] = new DateTime();
		$data['post_id'] = (int) $this->getParam('id');
		$id = CommentsModel::insert($data);
		$this->redirect("this#comment-$id");
	}
}
