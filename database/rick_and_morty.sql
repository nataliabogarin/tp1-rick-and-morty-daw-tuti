CREATE DATABASE rick_and_morty;

USE rick_and_morty;

CREATE TABLE LOCATION (
    id_location INT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type VARCHAR(100) NOT NULL,
    dimension VARCHAR(150) NOT NULL
);

CREATE TABLE CHARACTERS (
    id_character INT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    status VARCHAR(20) NOT NULL,
    species VARCHAR(100) NOT NULL,
    type VARCHAR(150) NOT NULL,
    gender VARCHAR(20) NOT NULL,
    image VARCHAR(255) NOT NULL,
    id_origin_location INT NULL,
    id_current_location INT NULL,
    FOREIGN KEY (id_origin_location) REFERENCES LOCATION(id_location),
    FOREIGN KEY (id_current_location) REFERENCES LOCATION(id_location)
);

CREATE TABLE EPISODE (
    id_episode INT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    air_date VARCHAR(50) NOT NULL,
    episode VARCHAR(10) NOT NULL
);

CREATE TABLE CHARACTERS_EPISODE (
    id_character INT,
    id_episode INT,
    PRIMARY KEY (id_character, id_episode),
    FOREIGN KEY (id_character) REFERENCES CHARACTERS(id_character),
    FOREIGN KEY (id_episode) REFERENCES EPISODE(id_episode)
);