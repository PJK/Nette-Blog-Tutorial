<?php

use Nette\Application\AppForm;

class HomepagePresenter extends BasePresenter
{
	public function renderDefault()
	{
		$this->template->posts = PostsModel::fetchAll();
	}

	public function renderSingle($id = 0)
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
				->addRule(AppForm::FILLED, 'To se neumíš ani podepsat?!');
		$form->addTextArea('body', 'Komentář')
				->addRule(AppForm::FILLED, 'Komentář je povinný!');
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
		$this->flashMessage('Komentář uložen!');
		$this->redirect("this#comment-$id");
	}
}
