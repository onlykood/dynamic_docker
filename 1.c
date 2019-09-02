#include "stdio.h"
#include "stdlib.h"
#include "string.h"
#include "unistd.h"
#include <sys/wait.h>

int main(int argc, char const *argv[])
{
	if(argc<2){
		printf("eg: docker ps -q");
		exit(0);
	}
 	if(strncmp("docker",argv[1],6) && strncmp("at",argv[1],2)){
 		printf("{\"code\":\"0\",\"data\":\"Command Not Allow!\"}");
 		exit(0);
 	}
	if (fork() == 0){
		if (execvp((const char *)argv[1],(char * const *)&argv[1]) <0 ){
			printf("{\"code\":\"0\",\"data\":\"Command exec Error!\"}");
			exit(0);
		}
	}
	wait(NULL);
	return 0;
}