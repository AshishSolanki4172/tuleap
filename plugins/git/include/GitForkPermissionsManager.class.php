<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * GitForkPermissionsManager
 */
class GitForkPermissionsManager {

    /** @var GitRepository */
    private $repository;

    /** @var User_ForgeUserGroupFactory */
    private $user_group_factory;

    /** @var PermissionsManager */
    private $permissions_manager;

    public function __construct(GitRepository $repository) {
        $this->repository          = $repository;
        $this->user_group_factory  = new User_ForgeUserGroupFactory(new UserGroupDao());
        $this->permissions_manager = PermissionsManager::instance();
    }

    /**
     * Wrapper
     *
     * @return ProjectManager
     */
    function getProjectManager() {
        return $this->repository->_getProjectManager();
    }

    /**
     * Wrapper
     *
     * @return Codendi_HTMLPurifier
     */
    function getPurifier() {
        return Codendi_HTMLPurifier::instance();
    }

    /**
     * Prepare fork destination message according to the fork scope
     *
     * @param Array $params Request params
     *
     * @return String
     */
    private function displayForkDestinationMessage($params) {
        if ($params['scope'] == 'project') {
            $project         = $this->getProjectManager()->getProject($params['group_id']);
            $destinationHTML = $GLOBALS['Language']->getText('plugin_git', 'fork_destination_project_message',  array($project->getPublicName()));
        } else {
            $destinationHTML = $GLOBALS['Language']->getText('plugin_git', 'fork_destination_personal_message');
        }
        return $destinationHTML;
    }

    /**
     * Prepare fork repository message
     *
     * @param String $repos Comma separated repositories Ids selected for fork
     *
     * @return String
     */
    private function displayForkSourceRepositories($repos) {
        $dao             = new GitDao();
        $repoFactory     = new GitRepositoryFactory($dao, $this->getProjectManager());
        $sourceReposHTML = '';
        $repositories    = explode(',', $repos);

        foreach ($repositories as $repositoryId) {
            $repository       = $repoFactory->getRepositoryById($repositoryId);
            $sourceReposHTML .= '"'.$this->getPurifier()->purify($repository->getFullName()).'" ';
        }
        return $sourceReposHTML;
    }

    /**
     * Fetch the html code to display permissions form when forking repositories
     *
     * @param Array   $params   Request params
     * @param Integer $groupId  Project Id
     * @param String  $userName User name
     *
     * @return String
     */
    public function displayRepositoriesPermissionsForm($params, $groupId, $userName) {
        $sourceReposHTML = $this->displayForkSourceRepositories($params['repos']);
        $form  = '<h2>'.$GLOBALS['Language']->getText('plugin_git', 'fork_repositories').'</h2>';
        $form .= $GLOBALS['Language']->getText('plugin_git', 'fork_repository_message', array($sourceReposHTML));
        $form .= $this->displayForkDestinationMessage($params);
        $form .= '<h3>Set permissions for the repository to be created</h3>';
        $form .= '<form action="" method="POST">';
        $form .= '<input type="hidden" name="group_id" value="'.(int)$groupId.'" />';
        $form .= '<input type="hidden" name="action" value="do_fork_repositories" />';
        $token = new CSRFSynchronizerToken('/plugins/git/?group_id='.(int)$groupId.'&action=fork_repositories');
        $form .= $token->fetchHTMLInput();
        $form .= '<input id="fork_repositories_repo" type="hidden" name="repos" value="'.$this->getPurifier()->purify($params['repos']).'" />';
        $form .= '<input id="choose_personal" type="hidden" name="choose_destination" value="'.$this->getPurifier()->purify($params['scope']).'" />';
        $form .= '<input id="to_project" type="hidden" name="to_project" value="'.$this->getPurifier()->purify($params['group_id']).'" />';
        $form .= '<input type="hidden" id="fork_repositories_path" name="path" value="'.$this->getPurifier()->purify($params['namespace']).'" />';
        $form .= '<input type="hidden" id="fork_repositories_prefix" value="u/'. $userName .'" />';
        $form .= $this->displayAccessControl($groupId);
        $form .= '<input type="submit" class="btn btn-primary" value="'.$GLOBALS['Language']->getText('plugin_git', 'fork_repositories').'" />';
        $form .= '</form>';
        return $form;
    }

    /**
     * Display access control management for gitolite backend
     *
     * @param Integer $project_id Project Id, to manage permissions when performing a cross project fork
     *
     * @return String
     */
    public function displayAccessControl($project_id = null) {
        $project = ($project_id) ? ProjectManager::instance()->getProject($project_id) : $this->repository->getProject();

        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');
        $presenter = new GitPresenters_AccessControlPresenter(
            $this->repository->isMigratedToGerrit() ? true : false,
            'repo_access['.Git::PERM_READ.']',
            'repo_access['.Git::PERM_WRITE.']',
            'repo_access['.Git::PERM_WPLUS.']',
            $this->getOptions($project, Git::PERM_READ),
            $this->getOptions($project, Git::PERM_WRITE),
            $this->getOptions($project, Git::PERM_WPLUS)
        );

        return $renderer->renderToString('access-control', $presenter);
    }

    private function getOptions(Project $project, $permission) {
        $user_groups     = $this->user_group_factory->getAllForProject($project);
        $options         = array();
        $selected_values = $this->permissions_manager->getAuthorizedUGroupIdsForProject($project, $this->repository->getId(), $permission);

        foreach ($user_groups as $ugroup) {
            if ($ugroup->getId() == ProjectUGroup::ANONYMOUS && $permission !== Git::PERM_READ) {
                continue;
            }

            $selected  = in_array($ugroup->getId(), $selected_values) ? 'selected="selected"' : '';
            $options []= array(
                'value'    => $ugroup->getId(),
                'label'    => $ugroup->getName(),
                'selected' => $selected
            );
        }

        return $options;
    }
}