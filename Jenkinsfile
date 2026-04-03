pipeline {
    agent any

    tools {
        nodejs 'node'
    }

    environment {
        // Docker Configuration
        DOCKER_HOST = 'npipe:////./pipe/docker_engine'
        DOCKER_BUILDKIT = '0' 
        DOCKER_CONFIG = 'C:\\ProgramData\\Jenkins\\.jenkins\\.docker'
        
        // Absolute path to the Docker executable
        DOCKER_EXE = '"C:\\Program Files\\Docker\\Docker\\resources\\bin\\docker.exe"'
        
        // Paths for PHPUnit
        PHPUNIT_BIN = '"C:\\Users\\idash\\Demo_Project\\Stud_Perf\\vendor\\bin\\phpunit.bat"'
        DATABASE_TEST = '"C:\\Users\\idash\\Demo_Project\\Stud_Perf\\tests\\DatabaseTest.php"'
    }

    stages {
        stage('Checkout SCM') {
            steps {
                checkout scm
            }
        }

        stage('Frontend Tests') {
            steps {
                script {
                    // This runs for EVERY branch and EVERY Pull Request
                    bat 'npm install --legacy-peer-deps'
                    bat 'npx vitest run'
                }
            }
        }

        stage('Docker Deploy') {
            when {
                // This is more robust for Webhooks
                anyOf {
                    branch 'main'
                    expression { return env.BRANCH_NAME == 'main' }
                    expression { return env.GIT_BRANCH == 'origin/main' }
                }
            }
            steps {
                echo "Branch is 'main'. Proceeding with Deployment..."
                bat "${env.DOCKER_EXE} compose down"
                bat "${env.DOCKER_EXE} compose up -d --build"
                
                echo 'Waiting 20 seconds for Database to stabilize...'
                bat 'ping 127.0.0.1 -n 21 > nul' 
            }
        }

        stage('Backend & DB Tests') {
            // We run this after deploy to check the live database
            // If it's a PR, you might want to skip this or point to a test DB
            steps {
                bat "${env.PHPUNIT_BIN} ${env.DATABASE_TEST}"
            }
        }
        
        stage('Verify Deployment') {
            when{
            anyOf {
                    branch 'main'
                    expression { return env.BRANCH_NAME == 'main' }
                    expression { return env.GIT_BRANCH == 'origin/main' }
                }
            }
            steps {
                bat "${env.DOCKER_EXE} ps"
                echo "Deployment Successful: Student Performance Tracking System is Live."
            }
        }
    }

    post {
        always {
            echo 'Pipeline execution complete.'
        }
        failure {
            echo 'Pipeline Failed. Check logs for ERESOLVE or path errors.'
        }
    }
}