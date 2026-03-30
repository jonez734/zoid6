from optparse import OptionParser
import psycopg2
import psycopg2.extras


def databaseconnect(opts):
    kw = {}
    kw["database"] = opts.databasename
    kw["host"] = opts.databasehost

    return psycopg2.connect(connection_factory=psycopg2.extras.RealDictConnection, **kw)


def main():
    parser = OptionParser(usage="usage: %prog")
    parser.add_option(
        "--verbose",
        dest="verbose",
        default=False,
        action="store_true",
        help="verbose output",
    )
    parser.add_option(
        "--debug", dest="debug", default=False, action="store_true", help="debug mode"
    )

    parser.add_option(
        "--databasehost",
        dest="databasehost",
        default="localhost",
        action="store",
        help="the host where the database is stored",
    )
    parser.add_option(
        "--databasename",
        dest="databasename",
        default="craigslistbot",
        action="store",
        help="the name of the database",
    )

    (opts, args) = parser.parse_args()

    dbh = databaseconnect(opts)
    dbh.close()
