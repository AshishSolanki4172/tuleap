#!/usr/bin/env groovy

def actions

pipeline {
    agent {
        label 'docker'
    }

    stages {
        stage('Checkout') {
            steps {
                dir ('sources') {
                    deleteDir()
                }
                checkout changelog: false, poll: false, scm: [$class: 'GitSCM', branches: [[name: '*/master']], doGenerateSubmoduleConfigurations: false, extensions: [[$class: 'CloneOption', depth: 1, noTags: true, reference: '', shallow: true],[$class: 'CleanBeforeCheckout'], [$class: 'RelativeTargetDirectory', relativeTargetDir: 'sources']], submoduleCfg: [], userRemoteConfigs: [[credentialsId: 'gitolite-tuleap-net', url: 'ssh://gitolite@tuleap.net/tuleap/tuleap/stable.git']]]
                checkout scm
                sh 'git clone sources_plugin/ sources/plugins/baseline/'
            }
        }

        stage('Prepare') {
            agent {
                dockerfile {
                    dir 'sources/tools/utils/nix/'
                    filename 'build-tools.dockerfile'
                    reuseNode true
                    args '--tmpfs /tmp/tuleap_build:rw,noexec,nosuid --read-only'
                }
            }
            steps {
                dir ('results') {
                    deleteDir()
                }
                dir ('sources') {
                    withCredentials([
                        usernamePassword(
                            credentialsId: 'nexus.enalean.com_readonly',
                            passwordVariable: 'NPM_PASSWORD',
                            usernameVariable: 'NPM_USER'
                        ),
                        string(credentialsId: 'github-token-composer', variable: 'COMPOSER_GITHUB_AUTH')
                    ]) {
                        sh 'tools/utils/scripts/generated-files-builder.sh dev'
                    }
                }
            }
        }

        stage('Check lockfiles') {
            steps { script {
                actions = load 'sources/tests/actions.groovy'
                actions.runFilesStatusChangesDetection('plugins/baseline', 'lockfiles', 'package-lock.json composer.lock')
            } }
            post {
                failure {
                    dir ('sources/plugins/baseline') {
                        sh 'git diff'
                    }
                }
            }
        }

        stage('Tests') {
            failFast false
            parallel {
                stage('UT PHPUnit') {
                    stages {
                        stage('UT PHPUnit PHP 7.4') { steps { script { actions.runPHPUnitTests('74') } } }
                    }
                    post { always { junit 'results/ut-phpunit/*/phpunit_tests_results.xml' } }
                }
                stage('REST') {
                    stages {
                        stage('REST CentOS 6 PHP 7.4 MySQL 5.7') {
                            steps { script { actions.runRESTTests('mysql57', '74') } }
                        }
                    }
                    post { always { junit 'results/api-rest/*/rest_tests.xml' } }
                }
                stage('SOAP') {
                    stages {
                        stage('SOAP PHP 7.4') { steps { script { actions.runSOAPTests('mysql57', '74') } } }
                    }
                    post { always { junit "results/soap/*/soap_tests.xml" } }
                }
                stage ('Jest') {
                    agent {
                        docker {
                            image 'node:14.5-alpine'
                            reuseNode true
                            args '--network none'
                        }
                    }
                    steps { script { actions.runJestTests('Baseline', 'plugins/baseline/') } }
                    post {
                        always {
                            junit 'results/jest/test-*-results.xml'
                            publishCoverage adapters: [istanbulCoberturaAdapter('results/jest/coverage/cobertura-coverage.xml')], tag: 'Javascript'
                        }
                    }
                }
                stage('Check translation files') {
                    steps { script {
                        dir ('sources/plugins/baseline') {
                            sh "../../tests/files_status_checker/verify.sh 'translation files' '*.po\$'"
                        }
                    } }
                }
                stage('PHP coding standards') {
                    steps {
                        script {
                            actions.runPHPCodingStandards(
                                './src/vendor/bin/phpcs',
                                './tests/phpcs/tuleap-ruleset.xml',
                                'plugins/baseline/'
                            )
                        }
                    }
                }
                stage('SCSS coding standards') {
                    agent {
                        docker {
                            image 'node:14.5-alpine'
                            reuseNode true
                            args '--network none'
                        }
                    }
                    steps { script { actions.runStylelint() } }
                }
                stage('Build RPM') {
                    steps {
                        script {
                            dir ('sources/plugins/baseline') {
                                sh "TULEAP_PATH=${WORKSPACE}/sources ./build-rpm.sh"
                            }
                        }
                    }
                    post {
                        always {
                            archiveArtifacts "*.rpm"
                        }
                    }
                }
            }
            post {
                always {
                    archiveArtifacts allowEmptyArchive: true, artifacts: 'results/'
                }
                failure {
                    withCredentials([string(credentialsId: 'email-notification-baseline-plugin-team', variable: 'email')]) {
                        mail to: email,
                        subject: "${currentBuild.fullDisplayName} is broken",
                        body: "See ${env.BUILD_URL}"
                    }
                }
                unstable {
                    withCredentials([string(credentialsId: 'email-notification-baseline-plugin-team', variable: 'email')]) {
                        mail to: email,
                        subject: "Tuleap ${currentBuild.fullDisplayName} is unstable",
                        body: "See ${env.BUILD_URL}"
                    }
                }
            }
        }
        stage('Code conformity') {
            stages {
                stage('Check translation files') {
                    steps { script {
                        actions.runFilesStatusChangesDetection('plugins/baseline', 'translation files', '"*.po\$"')
                    } }
                }
            }
        }
    }
}
