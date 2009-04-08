#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>

#define BUFFER_LEN		4000

int main(int argc, char **argv)	{
	char buffer[BUFFER_LEN];
	char path[BUFFER_LEN];
	int duration = 0;
	int pid = 0;
	FILE *fd = NULL;
	if (argc<3)	{
		fprintf(stderr, "Usage: ./sleeper [TEMP_PATH] [DURATION]\n\n");
		return -1;
	}
/*
	strncpy(path, argv[0], BUFFER_LEN);
	if (path[0]!='/')	{
		strncpy(buffer, argv[0], BUFFER_LEN);
		getcwd(path, BUFFER_LEN);
		if ((buffer[0]=='.')&&(buffer[1]=='/'))	{
			strncat(path, buffer+1, BUFFER_LEN);
		} else	{
			strncat(path, "/", BUFFER_LEN);
			strncat(path, buffer, BUFFER_LEN);
		}
	}
	dirname(path);
	dirname(path);
	dirname(path);
	dirname(path);
	strncat(path, "/typo3temp/lockadmin/", BUFFER_LEN);
	pid = getpid();
	sprintf(buffer, "%s%d.pid", path, pid);
	fd = fopen(buffer, "wb");
	fclose(fd);
	fprintf(stdout, "%d", pid);
	fflush(stdout);
*/

	pid = getpid();
	sprintf(buffer, "%s%d.pid", argv[1], pid);
	fd = fopen(buffer, "wb");
	fclose(fd);
	fprintf(stdout, "%d", pid);
	fflush(stdout);

	duration = atoi(argv[2]);
	sleep(duration);

	return 0;
}

