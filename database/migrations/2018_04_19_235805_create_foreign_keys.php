<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;

class CreateForeignKeys extends Migration {

	public function up()
	{
		Schema::table('users', function(Blueprint $table) {
			$table->foreign('group_id')->references('id')->on('user_groups')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('users', function(Blueprint $table) {
			$table->foreign('role_id')->references('id')->on('user_roles')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('users', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('users')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('user_groups', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('companies', function(Blueprint $table) {
			$table->foreign('country_id')->references('id')->on('countries')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('user_roles', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('countries', function(Blueprint $table) {
			$table->foreign('lang_id')->references('id')->on('langs')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
		Schema::table('menus', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('menu_groups', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('contacts', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('configurations', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('actions', function(Blueprint $table) {
			$table->foreign('group_id')->references('id')->on('user_groups')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('actions', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('actions', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('events', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('events', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('contents', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('contents', function(Blueprint $table) {
			$table->foreign('task_id')->references('id')->on('tasks')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('contents', function(Blueprint $table) {
			$table->foreign('lang_id')->references('id')->on('langs')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('contents', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('accounts', function(Blueprint $table) {
			$table->foreign('country_id')->references('id')->on('countries')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('accounts', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('operations', function(Blueprint $table) {
			$table->foreign('account_id')->references('id')->on('accounts')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('operations', function(Blueprint $table) {
			$table->foreign('category_id')->references('id')->on('categories')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('operations', function(Blueprint $table) {
			$table->foreign('mode_id')->references('id')->on('categories')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('operations', function(Blueprint $table) {
			$table->foreign('contact_id')->references('id')->on('contacts')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('operations', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('projects', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('projects', function(Blueprint $table) {
			$table->foreign('program_id')->references('id')->on('categories')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('projects', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('tasks', function(Blueprint $table) {
			$table->foreign('project_id')->references('id')->on('projects')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('tasks', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('tasks', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('pivot_tasks_contacts', function(Blueprint $table) {
			$table->foreign('company_id')->references('id')->on('companies')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('pivot_tasks_contacts', function(Blueprint $table) {
			$table->foreign('contact_id')->references('id')->on('contacts')
						->onDelete('no action')
						->onUpdate('cascade');
		});
		Schema::table('pivot_tasks_contacts', function(Blueprint $table) {
			$table->foreign('task_id')->references('id')->on('tasks')
						->onDelete('no action')
						->onUpdate('cascade');
		});
	}

	public function down()
	{
		Schema::table('users', function(Blueprint $table) {
			$table->dropForeign('users_group_id_foreign');
		});
		Schema::table('users', function(Blueprint $table) {
			$table->dropForeign('users_role_id_foreign');
		});
		Schema::table('users', function(Blueprint $table) {
			$table->dropForeign('users_company_id_foreign');
		});
		Schema::table('user_groups', function(Blueprint $table) {
			$table->dropForeign('user_groups_company_id_foreign');
		});
		Schema::table('companies', function(Blueprint $table) {
			$table->dropForeign('companies_country_id_foreign');
		});
		Schema::table('user_roles', function(Blueprint $table) {
			$table->dropForeign('user_roles_company_id_foreign');
		});
		Schema::table('countries', function(Blueprint $table) {
			$table->dropForeign('countries_lang_id_foreign');
		});
		Schema::table('menus', function(Blueprint $table) {
			$table->dropForeign('menus_company_id_foreign');
		});
		Schema::table('menu_groups', function(Blueprint $table) {
			$table->dropForeign('menu_groups_company_id_foreign');
		});
		Schema::table('contacts', function(Blueprint $table) {
			$table->dropForeign('contacts_company_id_foreign');
		});
		Schema::table('configurations', function(Blueprint $table) {
			$table->dropForeign('configurations_company_id_foreign');
		});
		Schema::table('actions', function(Blueprint $table) {
			$table->dropForeign('actions_group_id_foreign');
		});
		Schema::table('actions', function(Blueprint $table) {
			$table->dropForeign('actions_user_id_foreign');
		});
		Schema::table('actions', function(Blueprint $table) {
			$table->dropForeign('actions_company_id_foreign');
		});
		Schema::table('events', function(Blueprint $table) {
			$table->dropForeign('events_user_id_foreign');
		});
		Schema::table('events', function(Blueprint $table) {
			$table->dropForeign('events_company_id_foreign');
		});
		Schema::table('contents', function(Blueprint $table) {
			$table->dropForeign('contents_user_id_foreign');
		});
		Schema::table('contents', function(Blueprint $table) {
			$table->dropForeign('contents_lang_id_foreign');
		});
		Schema::table('contents', function(Blueprint $table) {
			$table->dropForeign('contents_company_id_foreign');
		});
		Schema::table('accounts', function(Blueprint $table) {
			$table->dropForeign('accounts_country_id_foreign');
		});
		Schema::table('accounts', function(Blueprint $table) {
			$table->dropForeign('accounts_company_id_foreign');
		});
		Schema::table('operations', function(Blueprint $table) {
			$table->dropForeign('operations_account_id_foreign');
		});
		Schema::table('operations', function(Blueprint $table) {
			$table->dropForeign('operations_company_id_foreign');
		});
		Schema::table('projects', function(Blueprint $table) {
			$table->dropForeign('projects_user_id_foreign');
		});
		Schema::table('projects', function(Blueprint $table) {
			$table->dropForeign('projects_company_id_foreign');
		});
		Schema::table('tasks', function(Blueprint $table) {
			$table->dropForeign('tasks_project_id_foreign');
		});
		Schema::table('tasks', function(Blueprint $table) {
			$table->dropForeign('tasks_company_id_foreign');
		});

	}
}