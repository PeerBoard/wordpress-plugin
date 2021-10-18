pipeline {
    agent {
        label 'wp-devpeerboardclub'
    }
    stages {
        stage('Copy from git repo to peerboard plugin dir'){
            steps{
                sh 'sudo mkdir -p /opt/bitnami/apps/wordpress/htdocs/wp-content/plugins/peerboard'
                sh 'sudo rsync -av --delete ${PWD}/ /opt/bitnami/apps/wordpress/htdocs/wp-content/plugins/peerboard/'
                sh 'sudo rm -rf /opt/bitnami/apps/wordpress/htdocs/wp-content/plugins/peerboard/.git*'
            }
        }
        stage('List file directory'){
            steps{
                sh 'sudo ls -la /opt/bitnami/apps/wordpress/htdocs/wp-content/plugins/peerboard/'
            }
        }        
    }
}
