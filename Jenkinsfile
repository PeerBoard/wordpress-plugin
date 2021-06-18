pipeline {
    agent {
        label 'wp-devpeerboardclub'
    }
    stages {
        stage('copy from git repo to peerboard dir'){
            steps{
                sh 'sudo mkdir -p /opt/bitnami/apps/wordpress/htdocs/wp-content/peerboard'
                sh 'sudo rsync -av --delete ${PWD}/ /opt/bitnami/apps/wordpress/htdocs/wp-content/peerboard/'
                sh 'sudo rm -rf /opt/bitnami/apps/wordpress/htdocs/wp-content/peerboard/.git*'
            }
        }
        stage('print list of new files'){
            steps{
                sh 'sudo ls -la /opt/bitnami/apps/wordpress/htdocs/wp-content/peerboard/'
            }
        }        
    }
}
